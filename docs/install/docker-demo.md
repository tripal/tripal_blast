
# Trying out Tripal BLAST with Docker

Tripal BLAST comes with a docker file/image to provide a quick and easy way to checkout the module, as well as, to provide an easy way to contribute fixes/functionality.

!!! note "Requirements"

    If you don't yet have docker installed on your system there are instructions for installation here in the [Official Docker Documentation](https://docs.docker.com/engine/install/). If you are using MacOS or Windows, I highly recommend Docker Desktop (as a daily Mac user). The links to Docker desktop are in the official install documentation linked previously.

## Tripal BLAST Demo

We have already built the docker image for the Tripal BLAST demo and provided it on GitHub. You can run it locally as follows:

1. Pull the demo image from Github
```
docker pull ghcr.io/tripal/tripal_blast:demo
```
2. Use the run command to create a local container which will house the entire Tripal site and Tripal BLAST demonstration.
```
docker run -tid --name=TripalBLASTDemo --publish=80:80 ghcr.io/tripal/tripal_blast:demo
docker exec TripalBLASTDemo service postgresql restart
```
3. Go to http://localhost in your browser of choice and login in using the [Administrative username and password for the Tripal Docker](https://tripaldoc.readthedocs.io/en/latest/install/docker.html#development-site-information).

You now have a fully functional Tripal site with Tripal BLAST and NCBI Blast+ command-line utilities installed.

## Using Docker to Contribute

We would Love to collaborate with you! To use docker to spin up a development environment for Tripal BLAST we recommend building the demonstration image locally and then running a container that mounts your current git clone of our repository inside the container. This allows you to make code changes to the local git clone and see them immediately reflected within the image for testing purposes!

1. Using Github, make a fork of our repository by going to the following URL while logged into github: https://github.com/tripal/tripal_blast/fork or by clicking on "Fork" on our repository.
2. Clone your fork locally. We will use the main repository URL below but **you should substitute the URL of your fork here**.
```
mkdir ~/Dockers
cd ~/Dockers
git clone https://github.com/tripal/tripal_blast
cd tripal_blast
```
3. Create a branch for your contributions. When you eventually make a PR, you can commit to this branch to update the PR based on suggestions. This is why we recommend you not use the main branch of your repository. You can name the branch whatever you want (we used `my-feature-or-fix` in the below example).
```
git checkout -b my-feature-or-fix
```
4. Build the docker demonstration image.
```
docker build --tag=tripalblast:local ./
```
5. Create a container from the previous image in your current directory. The `--volume` part of this command will mount your current directory to the appropriate place inside the container.
```
docker run --publish=80:80 -tid --name=tripalblast --volume=`pwd`:/var/www/drupal/web/modules/contrib/tripal_blast tripalblast:local
docker exec tripalblast service postgresql restart
```

Now you can interact with the Tripal site in your browser in all the same ways you would a regular Tripal site by going to https://localhost and logging in using the [Administrative username and password for the Tripal Docker](https://tripaldoc.readthedocs.io/en/latest/install/docker.html#development-site-information).

You can also edit the files in your local git clone directly with the editor of your choice (e.g. VSCode or vim) and see the changes reflected immediately in the site.
