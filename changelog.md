Changelogs v4
---

WIP. Additional notes about changes not directly visible via commit messages

- removed sync reference for globals by default
- removed sync reference of HEADERS and SERVER[HTTP_*] vars
- removed reset to initial values of SERVER|ENV var on clear
- renamed beforeRoute & afterRoute hooks
- drop array access to hive
- removed FRAGMENT
- removed MB (mbstring ext now default)
- renamed view beforerender -> beforeRender
- renamed view afterrender -> afterRender
- added mock sandbox and various fixed
- removed Base non-clonable restriction
- LOCALES is null by default, to load locales, set a directoy path to LOCALES
