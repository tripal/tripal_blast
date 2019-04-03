Contribution Guidelines
========================

The following guidelines are meant to encourage contribution to Tripal BLAST UI source-code on GitHub by making the process open, transparent and collaborative.

Github Communication Tips
---------------------------
- Don't be afraid to mention people (@username) who are knowledgeable on the topic or invested.  *We are academics and overcommitted, it's too easy for issues to go unanswered: don't give up on us!*
- Likewise, don't be shy about bumping an issue if no one responds after a few days. *Balancing responsibilities is hard.*
- Want to get more involved? Issues marked with "Good beginner issue" are a good place to start if you want to try your hand at submitting a PR.
- Everyone is encouraged/welcome to comment on the issue queue! Tell us if you

    - are experiencing the same problem
    - have tried a suggested fix
    - know of a potential solution or work-around
    - have an opinion, idea or feedback of any kind!

- Be kind when interacting with others on Github! (see Code of Conduct below for further guidelines). We want to foster a welcoming, inclusive community!

    - Constructive criticism is welcome and encouraged but should be worded such that it is helpful :-) Direct criticism towards the idea or solution rather than the person and focus on alternatives or improvements.

Bugs
-----
- Every bug **should** be reported as a Github issue.

    - Even if a bug is found by a committer who intends to fix it themselves immediately, they **should** create an issue and assign it to themselves to show their intent.

- Please follow the issue templates as best you can.  This information makes discussion easier and helps us resolve the problem faster.

    - Also provide as much information as possible :-)  Screenshots or links to the issue on a development site can go a long way!

Feature Requests
------------------
- Every feature request should start as an issue so that discussion is encouraged :-)
- Please provide the following information (bold is required; underlined strengthens your argument):

    - **Use Case:** fully describe why you need/want this feature
    - Generally Applicable: Why do you feel this is generally applicable? Suggest other use cases if possible. Mention (@) others that might want/need this feature.
    - Implementation: Describe a possible implementation. Bonus points for configuration, use of ontologies, ease of use, permission control, security considerations

- All features **should** be optional so that site admin can choose to make it available to their users.

    - When applicable, new features should be designed such that site admin can disable them.
    - Bonus points: for making new features configurable and easily themed.

Pull Request (PR) Guideline
----------------------------
The goal of this document is to make it easy for **A)** contributors to make pull requests that will be accepted, and **B)** Tripal committers to determine if a pull request should be accepted.
- PRs that address a specific issue **must** link to the related issue page.

    - Really in almost every case, there should be an issue for a PR.  This allows feedback and discussion before the coding happens.  Not grounds to reject, but encourage users to create issues at start of their PR.  Better late than never :).

- Each PR **must** be tested/approved by at least one "trusted committer."

    - Testers **should** describe how the testing was performed if applicable (allows others to replicate the test).
    - Our guiding philosophy is to encourage open contribution.  With this in mind, committers should **work with contributors** to resolve issues in their PRs.  PRs that will not be merged should be closed, **transparently citing** the reason for closure.  In an ideal world, features that would be closed are discouraged at the **issue phase** before the code is written!
    - The pull request branch should be deleted after merging (if not from a forked repository) by the person who performs the merge.

- PRs **should** pass all Travis-CI tests before they are merged.
- Branches **should** follow the following format: [issue\_number]-[short\_description]
- **Must** follow `Drupal code standards: <https://www.drupal.org/docs/develop/standardshttps://www.drupal.org/docs/develop/standards>`_
- PRs for new feature should remain open until adequately discussed (see guidelines below).

.. note::

  If you need more instructions creating a pull request, see for example the `KnowPulse workflow <https://github.com/UofS-Pulse-Binfo/KnowPulse/blob/master/Workflow.md)>`_

Code of Conduct
----------------

- Be nice!  If that's insufficient, Tripal community defers to https://www.contributor-covenant.org/
