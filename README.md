# livetextadventures

This is the code used by myself, and a couple of others when we ran Live Text Adventures for First Play Sheffield. It's based on a framework I wrote when I first got out of uni and which is unbearably terrible for me to look at now, but I only had one evening to throw this together and it was the first thing that came to hand.

The idea for Live Text Adventures originally came from GameCity and they are in every way responsible for its awesomeness.

Setup:

 - Setup your database using db_setup.sql
 - Rename _data/config/www_yourdomain_com_settings.php to match your domain with all dots replaced with underscores (ip addresses will work fine) and update the settings within.
 - Update the RewriteBase line in .htaccess to reference the path to the the code will be served from (by default it assumes http://www.yourdomain.com/lta/)
 
Usage:
 
The spectator page: /jumbotron
The player page: /player/index/[playerid]
The writer page: /writer/index/[writerid]

Writers will have to be inserted manually into the database.
Players can be inserted on the admin page: /admin. This page will end all currently active stories. The spectator and writer pages will need refreshing after this to switch to the new stories.