<?php

namespace WP_CLI\Dispatcher;

/**
 * A non-leaf node in the command tree.
 * Contains one or more Subcommands.
 *
 * @package WP_CLI
 */
class CompositeCommand {

	protected $name, $shortdesc, $synopsis;

	protected $parent, $subcommands = array();

	/**
	 * Instantiate a new CompositeCommand
	 *
	 * @param mixed $parent Parent command (either Root or Composite)
	 * @param string $name Represents how command should be invoked
	 * @param \WP_CLI\DocParser
	 */
	public function __construct( $parent, $name, $docparser ) {
		$this->parent = $parent;

		$this->name = $name;

		$this->shortdesc = $docparser->get_shortdesc();
		$this->longdesc = $docparser->get_longdesc();

		$when_to_invoke = $docparser->get_tag( 'when' );
		if ( $when_to_invoke ) {
			\WP_CLI::get_runner()->register_early_invoke( $when_to_invoke, $this );
		}
	}

	/**
	 * Get the parent composite (or root) command
	 *
	 * @return mixed
	 */
	public function get_parent() {
		return $this->parent;
	}

	/**
	 * Add a named subcommand to this composite command's
	 * set of contained subcommands.
	 *
	 * @param string $name Represents how subcommand should be invoked
	 * @param \WP_CLI\Dispatcher\Subcommand
	 */
	public function add_subcommand( $name, $command ) {
		$this->subcommands[ $name ] = $command;
	}

	/**
	 * Composite commands always contain subcommands.
	 *
	 * @return true
	 */
	public function can_have_subcommands() {
		return true;
	}

	/**
	 * Get the subcommands contained by this composite
	 * command.
	 *
	 * @return array
	 */
	public function get_subcommands() {
		ksort( $this->subcommands );

		return $this->subcommands;
	}

	/**
	 * Get the name of this composite command.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Get the short description for this composite
	 * command.
	 *
	 * @return string
	 */
	public function get_shortdesc() {
		return $this->shortdesc;
	}

	/**
	 * Get the long description for this composite
	 * command.
	 *
	 * @return string
	 */
	public function get_longdesc() {
		return $this->longdesc;
	}

	/**
	 * Get the synopsis for this composite command.
	 * As a collection of subcommands, the composite
	 * command is only intended to invoke those
	 * subcommands.
	 *
	 * @return string
	 */
	public function get_synopsis() {
		return '<command>';
	}

	/**
	 * Get the usage for this composite command.
	 *
	 * @return string
	 */
	public function get_usage( $prefix ) {
		return sprintf( "%s%s %s",
			$prefix,
			implode( ' ', get_path( $this ) ),
			$this->get_synopsis()
		);
	}

	/**
	 * Show the usage for all subcommands contained
	 * by the composite command.
	 */
	public function show_usage() {
		$methods = $this->get_subcommands();

		$i = 0;

		foreach ( $methods as $name => $subcommand ) {
			$prefix = ( 0 == $i++ ) ? 'usage: ' : '   or: ';

			\WP_CLI::line( $subcommand->get_usage( $prefix ) );
		}

		$cmd_name = implode( ' ', array_slice( get_path( $this ), 1 ) );

		\WP_CLI::line();
		\WP_CLI::line( "See 'wp help $cmd_name <command>' for more information on a specific command." );
	}

	/**
	 * When a composite command is invoked, it shows usage
	 * docs for its subcommands.
	 *
	 * @param array $args
	 * @param array $assoc_args
	 * @param array $extra_args
	 */
	public function invoke( $args, $assoc_args, $extra_args ) {
		$this->show_usage();
	}

	/**
	 * Given supplied arguments, find a contained
	 * subcommand
	 *
	 * @param array $args
	 * @return \WP_CLI\Dispatcher\Subcommand|false
	 */
	public function find_subcommand( &$args ) {
		$name = array_shift( $args );

		$subcommands = $this->get_subcommands();

		if ( !isset( $subcommands[ $name ] ) ) {
			$aliases = self::get_aliases( $subcommands );

			if ( isset( $aliases[ $name ] ) ) {
				$name = $aliases[ $name ];
			}
		}

		if ( !isset( $subcommands[ $name ] ) )
			return false;

		return $subcommands[ $name ];
	}

	/**
	 * Get any registered aliases for this composite command's
	 * subcommands.
	 *
	 * @param array $subcommands
	 * @return array
	 */
	private static function get_aliases( $subcommands ) {
		$aliases = array();

		foreach ( $subcommands as $name => $subcommand ) {
			$alias = $subcommand->get_alias();
			if ( $alias )
				$aliases[ $alias ] = $name;
		}

		return $aliases;
	}

	/**
	 * Composite commands can only be known by one name.
	 *
	 * @return false
	 */
	public function get_alias() {
		return false;
	}
}

