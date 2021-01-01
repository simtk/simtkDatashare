module.exports = function( $ ) {
	var sha256 = require( '/usr/lib/nodejs/npm/node_modules/js-sha256' );

	// ===== RULES
	this.generalizations = {};

	// ===== HELPER FUNCTIONS
	var generalize = function( context ) {
		var match = Object.keys( this.generalizations ).find(( generalization ) => {
			regex = this.generalizations[ generalization ];
			return regex.exec( context );
		});
		if( match === undefined ) { return context; }
		else                      { return match;   }
	};
	var isDiv = ( p ) => { return $( p ).is( 'div' ); };

	// ===== METADATA EXTRACTION
	this.addGeneralizations = function( generalizations ) {
		Object.assign( this.generalizations, generalizations );
		var g = {};
		Object.keys( this.generalizations ).forEach(( generalization ) => {
			var regex = this.generalizations[ generalization ];
			g[ generalization ] = $( 'div' ).filter(( i, d ) => {
				var name = $( d ).attr( 'name' );
				if( name === undefined ) { return false; }
				return name.match( regex );
			});
			var debug = [];
			g[ generalization ].each(( i, d ) => { debug.push( $( d ).attr( 'name' )); });
		});
		return g;
	};

	// ============================================================
	this.context = function( metadatafile ) {
	// ============================================================
	/*
		@brief Returns an array of human-readable parent directories of the given file
	*/
		var context = this.path( metadatafile ).map(( c ) => { return c.humanize().toString(); });
		if( context.length > 1 && context[ 0 ].match( /subject/i )) { context = context.slice( 1 ); }
		return context;
	};

	// ============================================================
	this.path = function( metadatafile ) {
	// ============================================================
	/*
		@brief Returns an array containing the parent directories of the given file
	*/
		return $.grep( $( metadatafile ).parents(), isDiv ).reverse().map(( c ) => { return $( c ).attr( 'name' ); }).map( generalize );
	};

	// ============================================================
	this.pathfile = function( metadatafile ) {
	// ============================================================
	/*
		@brief Returns the path of the given file
	*/
		return $.grep( $( metadatafile ).parents(), isDiv ).reverse().map(( c ) => { return $( c ).attr( 'name' ); }).concat([ $( metadatafile ).attr( 'name' ) ]).join( '/' );
	};

	this.id = function( field ) {
		return sha256( field ).substr( 0, 16 );
	};

	this.operators = function( input, type ) {
		var numeric   = [ "equal", "less", "less_or_equal", "greater", "greater_or_equal", "not_equal" ];
		var contains  = [ "contains" ];
		var equals    = [ "equals" ];
		var selected  = [ "in" ];
		var operators = {
			'text':     { 'double':   numeric,  'integer':  numeric,  'date':     numeric,  'time':     numeric,  'datetime': numeric,  'string':   contains, 'boolean': equals   },
			'textarea': { 'double':   numeric,  'integer':  numeric,  'date':     numeric,  'time':     numeric,  'datetime': numeric,  'string':   contains, 'boolean': equals   },
			'radio':    { 'double':   equals,   'integer':  equals,   'date':     equals,   'time':     equals,   'datetime': equals,   'string':   equals,   'boolean': equals,  },
			'select':   { 'double':   equals,   'integer':  equals,   'date':     equals,   'time':     equals,   'datetime': equals,   'string':   equals,   'boolean': equals,  },
			'checkbox': { 'double':   selected, 'integer':  selected, 'date':     selected, 'time':     selected, 'datetime': selected, 'string':   selected, 'boolean': selected },
		};
		if( input in operators && type in operators[ input ]) { return operators[ input ][ type ]; }
		return undefined;
	}

	this.select = function( selector ) {
		Object.keys( this.generalizations ).forEach(( generalization ) => {
			var find    = new RegExp( '\\[\\s*name=' + generalization + '\\s*\\]' );
			var replace = '[name*=' + generalization + ']';
			selector = selector.replace( find, replace );
		});
		return $( selector );
	};

	// ===== METADATA FILES SHORTCUT
	this.metadata = this.select( '.metadata.file' );

	return this;
};
