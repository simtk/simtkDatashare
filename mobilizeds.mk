snapshot:
	@echo Manually creating a snapshot of the filestore for users to download
	sudo perl bin/snapshot/dailyx

cleanup:
	@echo Manually cleaning up the downloads directory of old queries \(see script for minimum age\)
	sudo perl bin/cleanup/downloads
	sudo perl bin/cleanup/releases
