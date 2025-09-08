Changelogs v4
---

WIP. Additional notes about changes not directly visible via commit messages

- removed sync reference for globals by default
- removed sync reference of HEADERS and SERVER[HTTP_*] vars
- removed reset to initial values of SERVER|ENV var on clear
- renamed beforeRoute & afterRoute hooks
- dropped array access to hive
- removed FRAGMENT
- removed MB var (mbstring ext now default)
- renamed view beforerender -> beforeRender
- renamed view afterrender -> afterRender
- renamed route hooks beforeroute -> beforeRoute
- renamed route hooks afterroute -> afterRoute
- renamed all DB mapper hooks beforesave -> beforeSave, etc.
- added mock sandbox and various fixed
- removed Base non-clonable restriction
- LOCALES is null by default, to load locales, set a directory path to LOCALES
- language() is now able to set fallback and load locales as well to work more efficiently
- removed PLUGINS var
- removed $src on Base->ref because it doesn't fit well anymore on new Hive
- SERIALIZER no longer switches automatically when igbinary is installed and must be set manually
