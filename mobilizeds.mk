snapshot:
	@echo Manually creating a snapshot of the filestore for users to download
	/usr/bin/perl bin/snapshot/dailyx

cleanup:
	@echo Manually cleaning up the downloads directory of old queries \(see script for minimum age\)
	/usr/bin/perl bin/cleanup/downloads
	/usr/bin/perl bin/cleanup/releases
