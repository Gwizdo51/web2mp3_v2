# web2mp3_v2
The same web project (download songs from YouTube), but with Angular and Symfony + API Platform

## Build & serve front-end

From the `front` folder:
- `ng build`
- `rm -rf /srv/static/web2mp3_v2_front/browser/ && cp -r ./dist/front/browser/ /srv/static/web2mp3_v2_front/`

## TODO

- [x] Add new "deleted" state for downloads which files have been deleted
- [x] Redirect to same download if same link + format + quality + not deleted yet
- [ ] Add scheduler to update yt-dlp
- [ ] Add queue position broadcasting
- [ ] Set up logging
