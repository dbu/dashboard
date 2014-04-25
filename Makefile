deploy:
	rsync -azvC . linode:/var/www/davidbu.ch/cmf --exclude=app/cache --exclude=app/logs --exclude=web/assetic --exclude=web/bundles --exclude=web/css --exclude=web/js --exclude=app/config/parameters.yml --exclude=.idea --exclude=.git
	echo "run app/console --env=prod cache:clear && app/console --env=prod assetic:dump"
