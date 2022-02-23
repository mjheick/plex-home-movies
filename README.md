# plex-home-movies
A way to get "home movies" on plex media server

# Prerequisites

## Plex Media Server
[download](https://www.plex.tv/media-server-downloads/) and install. Currently this is developed and working against Version 1.24.4.5492

## WebTools
[support](https://forums.plex.tv/t/rel-webtools-unsupported-appstore/206843)|[download](https://github.com/ukdtom/WebTools.bundle/wiki/Install) and install (v3.0.0). Once you've installed it you will need to sign in, go to UAS (UnsupportedAppStore), go to Agent, and find/install XBMCnfoMoviesImporter and XMBCnfoTVImporter.

You can manually install XBMCnfoMoviesImporter via [github:gboudreau/XBMCnfoMoviesImporter.bundle](https://github.com/gboudreau/XBMCnfoMoviesImporter.bundle)

## Setting up your Plex Library
Create a new library with the *Plex Video Files Scanner* and the *XBMCnfoMoviesImporter* Agent


# Package Contents
The main driver behind this project is making sure NFO files are created and properly managed using the spec defined at [kodi.wiki/NFO_files](https://kodi.wiki/view/NFO_files/Movies).

Files written and executed against PHP 7.2+

## home-video-scanner.php
TL;DR, This scans all your media and creates the nfo files. This does it's best to prevent duplicates in your library, sets the "title" to the filename prefixed with CHANGEME-, estimates the year, and writes out a valid NFO file.

Set the variables and execute, and possibly set against a daily cron. Will only execute against files where an NFO does not exist for the unique filename generated for the filename.

## home-video-editor.php
TBD

