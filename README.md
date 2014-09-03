Copy-Lang
=========

Copy Lang copies language variables from one language pack to another. 

You can select the original language pack and this extension will only create languages variables used in the original pack. It will also only create the files used in the original pack. The variables from the "copy from" language pack will be substituded in the newly created language pack. Packs are used and saved in the store folder.
Especially for changed language files.

[![Build Status](https://travis-ci.org/ForumHulp/Copy_Lang.svg?branch=master)](https://travis-ci.org/ForumHulp/Copy_Lang)


## Requirements
* phpBB 3.1-dev or higher
* PHP 5.3 or higher

## Installation
You can install this on the latest copy of the develop branch ([phpBB 3.1-dev](https://github.com/phpbb/phpbb3)) by doing the following:

1. Copy the entire contents of this repo to to `FORUM_DIRECTORY/ext/forumhulp/copylang/`
2. Navigate in the ACP to `Customise -> Extension Management -> Extensions`.
3. Click Copy Language => `Enable`.

Note: This extension is in development. Installation is only recommended for testing purposes and is not supported on live boards. This extension will be officially released following phpBB 3.1.0.

## Uninstallation
Navigate in the ACP to `Customise -> Extension Management -> Extensions` and click Copy Language => `Disable`.

To permanently uninstall, click `Delete Data` and then you can safely delete the `/ext/forumhulp/copylang/` folder.

## License
[GNU General Public License v2](http://opensource.org/licenses/GPL-2.0)

© 2014 - John Peskens (ForumHulp.com)