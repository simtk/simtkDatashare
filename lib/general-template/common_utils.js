/**
 * Copyright 2020-2023, SimTK DataShare Team
 *
 * This file is part of SimTK DataShare. Initial development
 * was funded under NIH grants R01GM107340 and U54EB020405
 * and the U.S. Army Medical Research & Material Command award
 * W81XWH-15-1-0232R01. Continued maintenance and enhancement
 * are funded by NIH grant R01GM124443.
 */

simtkHumanize = function(theStr) {
	var humanized = theStr.trim().replace(/([a-z\d])([A-Z]+)/g, '$1_$2');
	humanized = humanized.replace(/([A-Z\d]+)([A-Z][a-z])/g, '$1_$2');
	humanized = humanized.replace(/[-\s]+/g, '_').toLowerCase();
	humanized = humanized.replace(/_id$/, '');
	humanized = humanized.replace(/_/g, " ");
	humanized = humanized.trim();
	humanized = humanized.charAt(0).toUpperCase() + humanized.slice(1);

	return humanized;
}

simtkHumanizeAttrName = function(attrName) {
	var fieldName   = attrName.toString();
	fieldName = fieldName.replace(/_/g, " ");
	fieldName = fieldName.replace(/[^\(\)\[\]\/\^0-9a-z ]/gi, '').trim();
	fieldName = fieldName.charAt(0).toUpperCase() + fieldName.slice(1);

	return fieldName;
}

