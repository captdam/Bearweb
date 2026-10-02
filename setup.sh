dir=/var/www/bearweb
bear=/var/www/bearweb
owner=admin_username:www-data

chown $owner $dir/bw_site.db
chown $owner $dir/bw_user.db
chown $owner $dir/bw_session.db
chmod 664 $dir/bw_site.db
chmod 660 $dir/bw_user.db
chmod 660 $dir/bw_session.db