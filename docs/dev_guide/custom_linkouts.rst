Custom Link-outs
=================

In Tripal BLAST "Linkouts" refer to changing the hit name in the BLAST results table to a link. This link usually gives the user additional information and may link to pages in your Tripal site, external websites or genome browsers. You can configure link-outs per BLAST Database and depending on the type, many link-outs support regular expression for extracting parts of the name. The types provided by Tripal BLAST also require you select a Tripal Database (Tripal > Chado Modules > Databases) which contains the URL information for the link. If the link-out types supplied by Tripal BLAST do not fit your needs you can create a custom type using the documentation below.

To create custom link-outs for Tripal BLAST you need to first create your own Drupal module. If you are unfamiliar with this process there are a number of good tutorial available in addition to the Drupal Documentation.

Once you have a custom module you need to implement hook_blast_linkout_info() to tell Tripal BLAST about your custom link-out. You do this by creating a function with the name of your module replacing the word "hook". For example:

.. code-block:: php

  /**
   * Implements hook_blast_linkout_info().
   * Provides a custom link-out type for my institutes genome browser.
   */
  function mymodule_blast_linkout_info() {
    $types = array();

    $types['mybrowser'] = array(
      // Human-readable Type name to display to users in the BLAST Database
      // create/edit form.
      'name' => 'UofS Browser',
      // The function used to generate the URL to be linked to.
      // This function will have full access to the blast hit and database
      // prefix information and is expected to return a URL.
      'process function' => 'mymodule_generate_linkout_mybrowser',
      // Help text to show in the BLAST Database create/edit form so that
      // users will know how to use this link-out type. Specifically, info
      // about your assumptions for the URL prefix are very helpful.
      // HTML is aloud but do not enclose in <p>.
      'help' => 'This type assumes your blast database is the reference for one
        of the University of Saskatchewan Genome Browsers and that you have selected
        the Tripal Database referencing that browser below.',
      // Whether or not the link-out requires additional fields from the nodes.
      'require_regex' => TRUE,
      'require_db' => TRUE,
    );

    return $types;
  }

Next you need to implement the process function that you indicated. This function is given a number of variables providing information about the hit, etc. and is expected to generate a fully rendered link based on that information. For example,

.. code-block:: php

  /**
   * Generate a link to the UofS Genome Browser for a given hit.
   *
   * @param $url_prefix
   *   The URL prefix for the BLAST Database queried.
   * @param $hit
   *   The blast XML hit object. This object has the following keys based on the
   *   XML: Hit_num, Hit_id, Hit_def, Hit_accession, Hit_len and Hit_hsps.
   *   Furthermore, a linkout_id key has beek added that contains the part of the
   *   Hit_def extracted using a regex provided when the blastdb node was created.
   * @param $info
   *   Additional information that may be useful in creating a link-out. Includes:
   *    - query_name: the name of the query sequence.
   *    - score: the score of the blast hit.
   *    - e-value: the e-value of the blast hit.
   * @param $options
   *   Any additional options needed to determine the type of link-out. None are
   *   supported by this particular link-out type.
   *
   * @return
   *   An html link.
   */
  function tripal_blast_generate_linkout_link($url_prefix, $hit, $info, $options = array()) {

    if (isset($hit->{'linkout_id'})) {

      // This is where you would generate your link. If your link requires query parameters
      // then we suggest you use l() $options['query'] to encode them rather than appending
      // them to the URL prefix directly.
      // This StackExchange question shows a good example:
      //   http://drupal.stackexchange.com/questions/38663/how-to-add-additional-url-parameters
      $hit_url = $url_prefix . $hit->{'linkout_id'};

      // See the documentation for l():
      //  https://api.drupal.org/api/drupal/includes%21common.inc/function/l/7
      return l(
        $hit->{'linkout_id'},
        $hit_url,
        array('attributes' => array('target' => '_blank'))
      );
    }
    else {
      return FALSE;
    }
  }
