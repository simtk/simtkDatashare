# Copyright 2020-2021, SimTK DataShare Team
#
# This file is part of SimTK DataShare. Initial development
# was funded under NIH grants R01GM107340 and U54EB020405
# and the U.S. Army Medical Research & Material Command award
# W81XWH-15-1-0232R01. Continued maintenance and enhancement
# are funded by NIH grant R01GM124443.

snapshot:
	@echo Manually creating a snapshot of the filestore for users to download
	/usr/bin/perl bin/snapshot/dailyx

cleanup:
	@echo Manually cleaning up the downloads directory of old queries \(see script for minimum age\)
	/usr/bin/perl bin/cleanup/downloads
	/usr/bin/perl bin/cleanup/releases
