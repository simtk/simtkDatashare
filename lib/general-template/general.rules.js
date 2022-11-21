/**
 * Copyright 2020-2022, SimTK DataShare Team
 *
 * This file is part of SimTK DataShare. Initial development
 * was funded under NIH grants R01GM107340 and U54EB020405
 * and the U.S. Army Medical Research & Material Command award
 * W81XWH-15-1-0232R01. Continued maintenance and enhancement
 * are funded by NIH grant R01GM124443.
 */

// For tracking number of type value mismatches.
cntTypeValueMismatches = 0;

var GeneralTemplate = function() {
	this.path            = '.',

	// ============================================================
	// CAPTURING COMMON DIRECTORY NAMING CONVENTIONS
	// ============================================================
	// Use these generalizations (e.g. subject01 is a subject) as 
	// g.subject for the subject directories.
	// ------------------------------------------------------------
	this.generalizations = function( rules ) {
		var g = rules.addGeneralizations({
			subjectSrc : /subjectSrc\d+/i,
		});

		return g;
	},

	// ============================================================
	// DYNAMIC QUERY SUPPORT
	// ============================================================
	this.dynamic_query   = function( $, rules ) {
		var g = this.generalizations( rules );

		// ============================================================
		// GENERAL TEMPLATE DATATYPES MAPPING TO QUERYBUILDER DATATYPES
		// ============================================================
		// See QueryBuilder's website (http://querybuilder.js.org) 
		// for more information.
		var ops = {
			bool:     [ 'in' ],
			category: [ 'contains' ],
			numeric:  [ 'equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal', 'not_equal' ],
			string:   [ 'contains' ],
		};
		var qbmap = { // Maps general input types to query builder input types
			'integer':  { input: 'text',     type: 'integer', operators: ops.numeric  },
			'double':   { input: 'text',     type: 'double',  operators: ops.numeric  },
			'text':     { input: 'text',     type: 'string',  operators: ops.string   },
			'radio':    { input: 'radio',    type: 'string',  operators: ops.category }, // Small list of mutually-exclusive categories
			'select':   { input: 'select',   type: 'string',  operators: ops.category }, // Large list of mutually-exclusive categories
			'checkbox': { input: 'checkbox', type: 'string',  operators: ops.bool }, // List of categories; multiple values possible
		};

		var filters = {}; // Filters are QueryBuilder's drop-down menus to build basic queries
		var units   = {};
		var add   = { 
			detailed : { filter: function( field, context ) {
				require('/usr/local/mobilizeds/lib/general-template/common_utils.js');
				var fieldname   = simtkHumanizeAttrName(field.attr('name'));
				var id          = rules.id(( context.concat([ fieldname ])).join( ' > ' ));
				var optgroup    = context[ 0 ];
				var label       = ( context.slice( 1 ).concat([ fieldname ])).join( ' > ' );
				var filter      = undefined;
				var type        = field.find( '[name=type]'  ).text();
				var unit        = field.find( '[name=unit]'  ).text();
				var value       = field.find( '[name=value]' ).text();
				var categorical = type.match( /^(?:radio|select|checkbox)$/ );

				if ((type.trim() != "" && 
					(value === undefined || value === null || value.trim() == "")) ||
					(value.trim() != "" &&
					(type === undefined || type === null || type.trim() == ""))) {
					// Mismatch of type/value.
					// Increment count for warning later..
					cntTypeValueMismatches++;
				}

				// ===== USE EXISTING FILTER, IF EXISTS; OTHERWISE CREATE A NEW FILTER
				if( id in filters ) { filter = filters[ id ]; }
				else {
					filter = filters[ id ] = { id: id, optgroup: optgroup, label: label };
					if( categorical ) { filter.values = []; }
					else              { filter.default_value = value; }
				} 
				if( unit ) { units[ id ] = { unit: unit }; }

				if( type in qbmap ) {
					var qb = qbmap[ type ];
					filter.type      = qb.type;
					filter.input     = qb.input;
					filter.operators = qb.operators;
				}

				// Concatenate the categorical filter value to the current list of values if it doesn't already exist
				if( categorical ) {
					var exists = filter.values.indexOf( value ) >= 0;
					if( ! exists ) { filter.values = filter.values.concat([ value ]).sort(); }
				}
			}},
			common: { filter: function( field, context ) {
				var fieldname   = simtkHumanizeAttrName(field.attr('name'));
				var id          = rules.id(( context.concat([ fieldname ] )).join( ' > ' ));
				var optgroup    = context[ 0 ];
				var label       = ( context.slice( 1 ).concat([ fieldname ])).join( ' > ' );
				var filter      = undefined;
				var type        = undefined;
				var unit        = undefined;
				var value       = field.text();

				if     ( fieldname.match( /^id$/i     )) { type = 'integer';                 }
				else if( fieldname.match( /^age$/i    )) { type = 'integer'; unit = 'years'; }
				else if( fieldname.match( /^weight$/i )) { type = 'double';  unit = 'kg';    }
				else if( fieldname.match( /^height$/i )) { type = 'double';  unit = 'cm';    }
				else if( fieldname.match( /^gender$/i )) { type = 'radio';                   }
				else if( $.isNumeric( value )  ) { type = Number.isInteger( value ) ? 'integer' : 'double'; } 
				else  	 	 	 	 	 	 	 { type = 'text';     }

				var categorical = type.match( /^(?:radio|select|checkbox)$/ );

				// ===== USE EXISTING FILTER, IF EXISTS; OTHERWISE CREATE A NEW FILTER
				if( id in filters ) { filter = filters[ id ]; }
				else {
					filter = filters[ id ] = { id: id, optgroup: optgroup, operators: [ 'contains' ], label: label };
					if( categorical ) { filter.values = []; }
					else              { filter.default_value = value; }
				} 
				if( unit ) { units[ id ] = { unit: unit }; }

				if( type in qbmap ) {
					var qb = qbmap[ type ];
					filter.type      = qb.type;
					filter.input     = qb.input;
					filter.operators = qb.operators;
				}

				// Concatenate the categorical filter value to the current list of values if it doesn't already exist
				if( categorical ) {
					var exists = filter.values.indexOf( value ) >= 0;
					if( ! exists ) { filter.values = filter.values.concat([ value ]).sort(); }
				}
			}}
		};

		// ============================================================
		// GENERAL TEMPLATE EXPLICIT METADATA
		// ============================================================
		// Find the metadata files and collect the fields
		// Files are generally dictionaries of dictionaries
		//	------------------------------------------------------------
		$( '.metadata.file' ).each(( i, file ) => {
			var context = rules.context( file );

			// First see if the metadata is organized as a dictionary of dictionaries
			// Initialize count of type/value mismatches.
			cntTypeValueMismatches = 0;
			var fields = $( file ).find( '.metadata.field-dict .metadata.field-dict' );
			fields.each(( i, field ) => { add.detailed.filter( $( field ), context ); });
			if (cntTypeValueMismatches > 0) {
				var thePath = rules.pathfile( file );
				var tmpIdx = rules.pathfile( file ).lastIndexOf("/");
				if (tmpIdx != -1) {
					// Get folder name.
					thePath = rules.pathfile( file ).substr(0, tmpIdx);
				}
				console.log("***warning***" + thePath +
					". Mismatched number of types and values.");
			}

			// Otherwise see if the metadata is organized as a simple dictionary
			if( fields.length == 0 ) { 
				fields = $( file ).find( '.metadata.field-dict .metadata.field' ); 
				fields.each(( i, field ) => { add.common.filter( $( field ), context ); });
			}

		});

		filters = Object.keys( filters ).map(( id ) => { return filters[ id ]; })
		return { filters: filters, units: units };
	},

	// ============================================================
	// NOSQL INDEX BUILDING SUPPORT
	// ============================================================
	this.indexer = function( $, rules ) {
		var g     = this.generalizations( rules );
		var index = [];
		var add = {
			detailed: {
				index : function( field, context, metadata, files ) {
					var fieldname = simtkHumanizeAttrName(field.attr('name'));
					var readable  = context.concat([ fieldname ]).join( ' > ' );
					var id        = rules.id( readable );
					var value     = field.find( '[name=value]' ).text();

					metadata[ id ] = value;
			}},
			common: {
				index : function( field, context, metadata, files ) {
					var fieldname = simtkHumanizeAttrName(field.attr('name'));
					var readable  = context.concat([ fieldname ]).join( ' > ' );
					var id        = rules.id( readable );
					var value     = field.text();

					metadata[ id ] = value;
			}}

		};

		var arrSubjAttrs = {};
		$( '.metadata.file' ).each(( i, file ) => {
			var tmpIdx = rules.pathfile( file ).lastIndexOf("/");
			if (tmpIdx != -1) {
				var subjData = {};
				// Get path to this file with attributes.
				var thePath = rules.pathfile( file ).substr(0, tmpIdx);
				// Get attributes.
				var context  = rules.context( file );
				var fields   = $( file ).find( '.metadata.field-dict .metadata.field-dict' );
				fields.each(( i, field ) => {
					add.detailed.index( $( field ), context, subjData );
				});
				// Otherwise see if the metadata is organized as a simple dictionary
				if ( fields.length == 0 ) { 
					fields = $( file ).find( '.metadata.field-dict .metadata.field' ); 
					fields.each(( i, field ) => {
						add.common.index( $( field ), context, subjData );
					});
				}
				// Keep attributes in associative array with path as index.
				arrSubjAttrs[thePath] = subjData;

				// ===== GET THE DOWNSTREAM FILES AFFECTED BY THIS METADATA
				var files = [];
				$( file ).siblings( '.folder' ).each(( i, item ) => { 

					if ($( item ).is( '.folder' )) {
						$( item ).find( '.file' ).each(( i, file ) => {
							// Downstream file.
							tmpIdx = rules.pathfile( file ).lastIndexOf("/");
							if (tmpIdx != -1) {
								subjData = {};
								// Get path to this file with attributes.
								thePath = rules.pathfile( file ).substr(0, tmpIdx);
								// Get attributes.
								context  = rules.context( file );
								fields   = $( file ).find( '.metadata.field-dict .metadata.field-dict' );
								fields.each(( i, field ) => {
									add.detailed.index( $( field ), context, subjData );
								});

								// Otherwise see if the metadata is organized 
								// as a simple dictionary
								if( fields.length == 0 ) { 
									fields = $( file ).find( '.metadata.field-dict .metadata.field' ); 
									fields.each(( i, field ) => {
										add.common.index( $( field ), context, subjData );
									});
								}
								arrSubjAttrs[thePath] = subjData;
							}
						}); 
					} 
				});
			}
		});

		$( '.metadata.file' ).each(( i, file ) => {
			var metadata = {};

			// ===== GET THE FILES AFFECTED BY THIS METADATA (SIBLINGS AND DOWNSTREAM)
			var files = [];
			$( file ).siblings( '.folder, .file' ).each(( i, item ) => { 
				if ( $( item ).is( '.folder' )) {
					$( item ).find( '.file' ).each(( i, file ) => {
						files.push( rules.pathfile( file ));
					});
				} 
				else if( $( item ).is( '.file' )) {
					files.push( rules.pathfile( item ));
				}
			});

			// Insert this metadata file.
			files.push( rules.pathfile( file ));

			metadata.files = files;

			var theSubjData = -1;
			var tmpIdx = rules.pathfile( file ).lastIndexOf("/");
			if (tmpIdx != -1) {
				// Look up path of this file with attributes.
				var thePath = rules.pathfile( file ).substr(0, tmpIdx);
				metadata["path"] = thePath;
				// Get attributes from ancestors including the file itself.
				$.each(arrSubjAttrs, function(pathSubjData, theSubjData) {
					if (thePath.indexOf(pathSubjData) != -1) {
						// Add attributes/values to metadata.
						$.each(theSubjData, function(attr, val) {
							metadata[attr] = val;
						});
					}
				});
			}

			index.push( metadata );
		});

		return index;
	}
};
module.exports = new GeneralTemplate();
