# IntegrityAdvocate Moodle availability restriction

Integrity Advocate does identity verification & participation monitoring. 
This availability restriction (condition) accompanies the moodle-block_integrityadvocate plugin to prevent access to a course module depending on the IA results in another module.

## Requirements ##
For requirements, see the moodle-block_integrityadvocate plugin
https://bitbucket.org/mwebv/moodle-block_integrityadvocate/src/master/README.md

## Installation ##
This plugin *REQUIRES* the moodle-block_integrityadvocate plugin is installed and *active in an activity* in the current course.

Important: Install the block_integrityadvocate plugin first!! Then go back and install this plugin.

Login to your Moodle site as an admin, navigate to Site administration > Plugins > Install plugins, upload the zip file and install it.

**or**

1. Copy the integrityadvocate directory into the availability/condition/ directory of your Moodle instance;
2. Browse to the Moodle admin notifications page and step through the installer.

## Setup ##
Once installed, you can use it like any other availability condition under activity settigs > Restrict access.

Ref https://docs.moodle.org/38/en/Restrict_access