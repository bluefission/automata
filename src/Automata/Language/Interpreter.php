<?php 
namespace BlueFission\Automata\Language;

class Interpreter implements IInterpreter {

	// private $_tokenizer;
	private $_grammar;
	private $_documenter;
	private $_walker;

	public function __construct( Grammar $grammar, Documenter $documenter, Walker $walker ) {
		$this->_grammar = $grammar;
		$this->_documenter = $documenter;
		$this->_walker = $walker;
	}

	public function isValid($code): bool
	{
		return false;
	}

	// Load Source
	public function load( $sourceFile ) {
		$contents = file_get_contents( $sourceFile );
		$this->run($contents);
	}

	// Inject Grammer into Tokenizer Parser
	// Read source code in Tokenizer Parser
	public function run( $code ) {
		$tokens = $this->_grammar->tokenize($code, $this->_grammar);

		// Read and run Tokenized data in Documenter
		foreach ( $tokens as $token ) {
			$this->_documenter->push($token);
		}

		// Documenter generates a tree
		$this->_documenter->processStatements();

		// Create a scenario tree
		$tree = $this->_documenter->getTree();

		// Traverse Statements with Walker
		// Push Statement into Walker	
		$this->_walker->traverse($tree);
		
		// Prompt statements generated by Composer
		$this->_walker->process();
	}

	public function getTree() {
		return $this->_documenter->getTree();
	}
}