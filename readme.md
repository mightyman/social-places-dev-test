# Introduction

### Welcome prospective developer.

#### Social Places
We are [Social Places](https://socialplaces.io); a local marketing software agency that provides listings, reputation, bookings and social interactions across multiple platforms and channels.
For a great summary of the services we offer, please watch our [2021 Product Overview](https://www.youtube.com/watch?v=CLQeB5pFpNw) [2:47] YouTube video.

#### Developers

We are looking for developer talent to join our team. Here you'll find a
basic application outfitted in our tech stack but in a much more limited sense.
To do the test, you'll find a brief containing some items to implement, issues
and other sorts of tasks to complete. In addition, there are some hidden
items that will award bonus points and some very impressed "examiner".

### Tech Stack:

1. Symfony 5.4
2. PHP 8.1
3. VueJs 2 (Upgrading to VueJs 3)
4. Docker (v20.10.7) and docker-compose (v2.5.0)

### How to Proceed:

1. Fork the repository to your own account.
2. Clone the forked repository using your preferred git client
    1. While it is not imperative that a git client such as [GitHub Desktop](https://desktop.github.com) or [Fork](https://git-fork.com/) is used we highly,
       highly recommend it.  
       We work as a team and need to be cognisant of each other's commits and quality of committed work.
3. Spin up the application using either:
    1. If you know how [Docker](https://www.docker.com/get-started/) works `docker-compose up` or `docker-compose start` (images are about 1gb)
        1. This will run `composer install` for you
        2. After Docker has started, in a new terminal window\tab, please run `docker-compose exec application bash`
        3. Next in the Docker bash run `console doctrine:schema:update --force` to ensure your database is up-to-date.
        4. The application will be served on [localhost](http://localhost) with mail running on [port 8025](http://localhost:8025)
    2. Else you can use the [Symfony CLI](https://symfony.com/download) tool
        1. You will need to run `composer install` first
        2. Set up your `.env` file to point to your database
            1. Yeah, you'll have to set your own database up
        3. From your project directory run `php bin\console doctrine:schema:update --force` to ensure your database is up-to-date.
        4. To start serving the content: `symfony server:start`
        5. The application will be served by the Symfony CLI
4. To develop the Vue and Vuetify front-end:
    1. Run `npm install` to get all the necessary packages
    2. Run `npm run watch` to watch for file changes and build the application for developing
5. Continue on your merry way with the test:
    1. Complete the brief as best as possible.
    2. The brief is not aimed to be tricky or misleading but designed to mimic a request we may have from management or clients.
    3. Commit regularly (but not unnecessarily or too small) - we want to see how you think and address each task.
    4. Take your time but hurry up slowly - while taking ones time is important for the task, don't take months.
6. Provide us with a link to your test, so we can examine it for you.

### Rubric:

Our rough rubric or measure is marked against a few things as follows:

1. PHP \ Vue \ Twig code:
    1. Follows perceived standards
        1. Tests perceptiveness (variable names, etc...)
        2. Ability to adjust to a new environment
    2. Produces quality code
        1. Code is readable
        2. Non-spaghetti like code
    3. Code navigation
    4. Task addressed accuracy
2. Git work:
    1. Was there an effort to use:
        1. Multiple commits
        2. Multiple branches (per task)
        3. Descriptive branch names and commit messages
3. Tests:
    1. Were tests written
        1. A loose metric - points for writing tests scaling by how much of the code and what they test
4. Language:
    1. Where language is used does it conform to a professional level and standard
        1. Things like swear words will get projects disqualified without further examination!
        2. Things like typos and misspelled words will tally against but not sway the marking heavily

It is important to note that we do not expect the best marks or even 50%, this test is character, willingness to adjust and to try.
