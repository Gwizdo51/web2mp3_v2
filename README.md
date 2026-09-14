# web2mp3_v2
The same web project (download songs from YouTube), but with Angular and Symfony + API Platform

## Build & serve front-end

From the `front` folder:
- `docker compose exec node ng build`
- `rm -rf /srv/static/web2mp3_front/browser/ && cp -r ./dist/front/browser/ /srv/static/web2mp3_front/`

Fom the `api` folder:
- `docker compose -f compose.yaml -f compose.prod.yaml build`
- `docker compose -f compose.yaml -f compose.prod.yaml up -d`

## TODO

- [x] Add new "deleted" state for downloads which files have been deleted
- [x] Redirect to same download if same link + format + quality + not deleted yet
- [x] Add queue position broadcasting
- [x] Add scheduler to update yt-dlp
- [x] Set up logging
