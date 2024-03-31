# WaterWolf Site

This is the WaterWolf Website

## Production

This repository is deployed to remote servers via the Docker image it builds as part of its GitHub Actions CI suite.

See `Dockerfile` for the build details, and the `.github` folder for the GitHub Actions details.

## Development

### Live Development Site

Live dev website can be found [https://dev.waterwolf.club](https://dev.waterwolf.club)

### Developing Locally

Developers running [Docker Desktop for Windows](https://www.docker.com/products/docker-desktop/) or MacOS or Docker
for Linux can take advantage of the built-in support for Docker and Docker Compose.

A `Makefile` also exists to allow easy shorthand access to common commands. You should have `make` installed on your
host OS to take advantage of this file, but all instructions are provided in both formats.

#### Initial Setup

Copy `dev.dist.env` to `dev.env` and update it with any missing secrets.

The database will be created from the DB migrations (in `/db/migrations`) on initial startup.

The following user accounts are created on local dev, all with the password `WaterWolf!`:

| UID | Username   | E-mail Address           |
|-----|------------|--------------------------|
| 1   | User       | user@waterwolf.dev       |
| 2   | TeamMember | teammember@waterwolf.dev |
| 3   | Moderator  | mod@waterwolf.dev        |
| 4   | Admin      | admin@waterwolf.dev      |
| 5   | Banned     | banned@waterwolf.dev     |

#### Building the Base Image

```bash
docker-compose build
# Or
make build
```

#### Spinning Up Containers

```bash
docker-compose up -d
# Or
make up
```

Your local instance will be available at https://localhost:8080.

#### Stopping Containers

```bash
docker-compose down
# Or
make down
```

To spin down all containers **and permanently delete volumes** (like DB data), run:

```bash
docker-compose down -v
```

#### Accessing Bash Shell Inside Container

As the `app` user:

```bash
docker-compose exec --user=app web bash
# Or
make bash
```

As the `root` user:

```bash
docker-compose exec --user=app web bash
# Or
make bash-root
```

## Asset Hosting

Static assets used by the web site are stored inside this repository and can be referenced directly via `/static` links.

User-uploaded content should instead be stored in the media storage subsystem, which resolves in
production to `media.waterwolf.town`.

`media.waterwolf.town` structure.
- site/        ->   `# Website assets`
  - css/
  - js/
  - img/
  - video/
  - uploads/   ->   `# User generated assets`
- public/      ->   `# Long-term file sharing`
  - unity/
  - video/
  - img/
