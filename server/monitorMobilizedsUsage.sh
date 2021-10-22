#!/bin/bash
if [ ! -e "/home/mobilizeds/study" ]
then
	# /home/mobilizeds/study directory does not exist.
	exit
fi
if [ ! -e "/home/mobilizeds/downloads" ]
then
	# /home/mobilizeds/downloads directory does not exist.
	exit
fi
if [ ! -e "/home/mobilizeds/releases" ]
then
	# /home/mobilizeds/releases directory does not exist.
	exit
fi

# Get size of /home/mobilizeds/study/ directory from previous cronjob check, if present.
oldSizeStudyDir=0
if [ -e "/opt/healthMonitors/sizeStudyDir" ]
then
	outOld=`cat /opt/healthMonitors/sizeStudyDir`
	# Put result into array.
	arrOutOld=($outOld)
	oldSizeStudyDir=${arrOutOld[0]}
fi
# Get current size of /home/mobilizeds/study/ directory.
du -s /home/mobilizeds/study > /opt/healthMonitors/sizeStudyDirNew
if [ -e "/opt/healthMonitors/sizeStudyDirNew" ]
then
	outNew=`cat /opt/healthMonitors/sizeStudyDirNew`
	# Put result into array.
	arrOutNew=($outNew)
	newSizeStudyDir=${arrOutNew[0]}
fi
# Set threshold at 1000000 KB (1GB).
theThreshold=1000000
# Has directory size increase exceeded threshold?
if ((($newSizeStudyDir - $oldSizeStudyDir) > $theThreshold)); then
	echo "/home/mobilizeds/study/ has grown more than $theThreshold KB; size is $newSizeStudyDir KB" | /usr/bin/mutt -s "`hostname`: Please check /home/mobilizeds/study/." -- webmaster@simtk.org
	# Replace with new size info.
	mv /opt/healthMonitors/sizeStudyDirNew /opt/healthMonitors/sizeStudyDir
else
	# Threshold not reached. Keep old size info. Alert when there is more increase.
	rm /opt/healthMonitors/sizeStudyDirNew
fi

# Get size of /home/mobilizeds/downloads/ directory from previous cronjob check, if present.
oldSizeDownloadsDir=0
if [ -e "/opt/healthMonitors/sizeDownloadsDir" ]
then
	outOld=`cat /opt/healthMonitors/sizeDownloadsDir`
	# Put result into array.
	arrOutOld=($outOld)
	oldSizeDownloadsDir=${arrOutOld[0]}
fi
# Get current size of /home/mobilizeds/downloads/ directory.
du -s /home/mobilizeds/downloads > /opt/healthMonitors/sizeDownloadsDirNew
if [ -e "/opt/healthMonitors/sizeDownloadsDirNew" ]
then
	outNew=`cat /opt/healthMonitors/sizeDownloadsDirNew`
	# Put result into array.
	arrOutNew=($outNew)
	newSizeDownloadsDir=${arrOutNew[0]}
fi
# Set threshold at 1000000 KB (1GB).
theThreshold=1000000
# Has directory size increase exceeded threshold?
if ((($newSizeDownloadsDir - $oldSizeDownloadsDir) > $theThreshold)); then
	echo "/home/mobilizeds/downloads/ has grown more than $theThreshold KB; size is $newSizeDownloadsDir KB" | /usr/bin/mutt -s "`hostname`: Please check /home/mobilizeds/downloads/." -- webmaster@simtk.org
	# Replace with new size info.
	mv /opt/healthMonitors/sizeDownloadsDirNew /opt/healthMonitors/sizeDownloadsDir
else
	# Threshold not reached. Keep old size info. Alert when there is more increase.
	rm /opt/healthMonitors/sizeDownloadsDirNew
fi

# Get size of /home/mobilizeds/releases/ directory from previous cronjob check, if present.
oldSizeReleasesDir=0
if [ -e "/opt/healthMonitors/sizeReleasesDir" ]
then
	outOld=`cat /opt/healthMonitors/sizeReleasesDir`
	# Put result into array.
	arrOutOld=($outOld)
	oldSizeReleasesDir=${arrOutOld[0]}
fi
# Get current size of /home/mobilizeds/releases/ directory.
du -s /home/mobilizeds/releases > /opt/healthMonitors/sizeReleasesDirNew
if [ -e "/opt/healthMonitors/sizeReleasesDirNew" ]
then
	outNew=`cat /opt/healthMonitors/sizeReleasesDirNew`
	# Put result into array.
	arrOutNew=($outNew)
	newSizeReleasesDir=${arrOutNew[0]}
fi
# Set threshold at 1000000 KB (1GB).
theThreshold=1000000
# Has directory size increase exceeded threshold?
if ((($newSizeReleasesDir - $oldSizeReleasesDir) > $theThreshold)); then
	echo "/home/mobilizeds/releases/ has grown more than $theThreshold KB; size is $newSizeReleasesDir KB" | /usr/bin/mutt -s "`hostname`: Please check /home/mobilizeds/releases/." -- webmaster@simtk.org
	# Replace with new size info.
	mv /opt/healthMonitors/sizeReleasesDirNew /opt/healthMonitors/sizeReleasesDir
else
	# Threshold not reached. Keep old size info. Alert when there is more increase.
	rm /opt/healthMonitors/sizeReleasesDirNew
fi


