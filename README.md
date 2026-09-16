# web2mp3_v2
The same web project (download songs from YouTube), but with Angular and Symfony + API Platform

## Build & serve front-end

From the `front` folder:
- `docker compose exec node ng build`
- `rm -rf /srv/static/web2mp3_front/browser/ && cp -r ./dist/front/browser/ /srv/static/web2mp3_front/`

Fom the `api` folder:
- `docker compose -f compose.yaml -f compose.prod.yaml build`
- `docker compose -f compose.yaml -f compose.prod.yaml up -d`

### Logrotate

```
/home/arthur/code/projects/web2mp3_v2/api/var/log/{dev,prod}.log {
    rotate 8
    weekly
    size 100k
    missingok
}
```
