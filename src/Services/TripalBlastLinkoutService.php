<?php

namespace Drupal\tripal_blast\Services;

use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Implements linkouts for blast hit sequences.
 *
 * Extendable through the hook_blast_linkout_info hook.
 */
class TripalBlastLinkoutService {

  use StringTranslationTrait;

  /**
   * Returns a list of supported linkout types for this module.
   *
   * This module's implementation of hook_blast_linkout_info will
   * fetch the information from this function.
   *
   * @return array
   *   The list of linkout types.
   */
  public function getLinkoutTypes(): array {
    $types = [];

    // A default link-out type requiring no information.
    $types['none'] = [
      'name' => $this->t('None'),
#@todo process function will be obsolete, replace with service
      'process function' => 'tripal_blast_generate_linkout_none',
      'service' => 'tripal_blast.linkout_service',
      'help' => $this->t('This will leave the blast results hits as plain text.'),
      'require_regex' => FALSE,
      'require_db' => FALSE,
    ];

    $types['link'] = [
      // Human-readable Type name to display to users in the BLAST Database
      // create/edit form.
      'name' => $this->t('Generic Link'),
      // The function used to generate the URL to be linked to.
      // This function will have full access to the blast hit and database
      // prefix information and is expected to return a URL.
      'process function' => 'tripal_blast_generate_linkout_link',
      'service' => 'tripal_blast.linkout_service',
      // Help text to show in the BLAST Database create/edit form so that
      // users will know how to use this link-out type. Specifically, info
      // about your assumptions for the URL prefix are very helpful.
      // HTML is aloud but do not enclose in <p>.
      'help' => $this->t('The External Database choosen below provides its URL prefix
        when determining the URL to link-out to. If the link-out type is
        "Generic Link" then the hit identifier (determined using fasta
        header format or regular expression) is concatenated to the end of
        the url prefix. For example, if your hit is for "Chr01" and the URL
        prefix is "http://myfriendstripalsite.org/name/" then the complete
        URL is simply
        &#60;a href="http://myfriendstripalsite.org/name/Chr01"&#62;Chr01&#60;/a&#62;.'),
      // Whether or not the link-out requires additional fields from the nodes.
      'require_regex' => TRUE,
      'require_db' => TRUE,
    ];

    $types['jbrowse'] = [
      'name' => $this->t('JBrowse'),
      'process function' => 'tripal_blast_generate_linkout_jbrowse',
      'service' => 'tripal_blast.linkout_service',
      'help' => $this->t('The link created will add a "Blast Result" track to the
        JBrowse (specified by the External Database) that shows the HSPs as
        well as indicating the overall hit. <strong><em>It is assumed that
        the Reference of the JBrowse is the same as this BLAST database
        (even the names must be consistent).</em></strong> Furthermore, the
        URL prefix supplied is expected to have an empty query (?) or be
        properly ended (&). For example,
        "https://mydomain.com/jbrowse/tripalus_databasica/?" OR
        "https://mydomain.com/jbrowse/tripalus_databasica/?tracks=genes,markers,blast&".
        Also <strong><em>the Blast Result track is NOT Displayed by
        default</em></strong>. Either include "blast" using the "tracks"
        directive in the URL prefix or specify it in your JBrowse.conf.'),
      // Whether or not the link-out requires additional fields from the nodes.
      'require_regex' => TRUE,
      'require_db' => TRUE,
    ];

    return $types;
  }

  /**
   * Implements this modules linkout hook types.
   *
   * Other modules can define other linkout types, definitions are added with
   * hook_blast_linkout_info and the module will define its own instance of
   * this function.
   *
   * @param string $linkout_type
   *   The type of linkout to be generated. This type may be defined
   *   either here or in another module.
   * @param string $url_prefix
   *   The URL prefix for the BLAST Database queried.
   * @param \SimpleXMLElement $hit
   *   The blast XML hit object. This object has the following keys based on the
   *   XML: Hit_num, Hit_id, Hit_def, Hit_accession, Hit_len and Hit_hsps.
   *   Furthermore, a linkout_id key has been added that contains the part of
   *   the Hit_def extracted using a regex provided when the blastdb record was
   *   created.
   * @param array $info
   *   Additional information that may be useful in creating a link-out.
   *   This includes:
   *    - query_name: the name of the query sequence.
   *    - score: the score of the blast hit.
   *    - e-value: the e-value of the blast hit.
   * @param array $options
   *   Any additional options needed to determine the type of link-out.
   *
   * @return Link|null
   *   An html link if type is supported, or NULL if not.
   */
  public function createLinkout(string $linkout_type, string $url_prefix, \SimpleXMLElement $hit, array $info, array $options = []): ?Link {
    $link = NULL;
    if ($linkout_type === 'none') {
      $link = NULL;
    }
    elseif ($linkout_type === 'link') {
      $link = $this->handleLink($url_prefix, $hit, $info, $options );
    }
    elseif ($linkout_type === 'jbrowse') {
      $link = $this->handleJbrowse($url_prefix, $hit, $info, $options );
    }
    return $link;
  }

  /**
   * Handle the 'link' linkout type.
   *
   * @param string $url_prefix
   *   The URL prefix for the BLAST Database queried.
   * @param object $hit
   *   The blast XML hit object. This object has the following keys based on the
   *   XML: Hit_num, Hit_id, Hit_def, Hit_accession, Hit_len and Hit_hsps.
   *   Furthermore, a linkout_id key has beek added that contains the part of
   *   the Hit_def extracted using a regex provided when the blastdb node was
   *   created.
   * @param array $info
   *   Additional information that may be useful in creating a link-out.
   *   This includes:
   *    - query_name: the name of the query sequence.
   *    - score: the score of the blast hit.
   *    - e-value: the e-value of the blast hit.
   * @param array $options
   *   Any additional options needed to determine the type of link-out.
   *   None are used for this linkout type, parameter is here for consistency.
   *
   * @return Link|null
   *   An html link if supported, or NULL if not.
   */
  protected function handleLink(string $url_prefix, object $hit, array $info, array $options = []): ?Link {
    $link = NULL;
    if (isset($hit->{'linkout_id'})) {
      $hit_url = $url_prefix . $hit->linkout_id;
      try {
        $url = Url::fromUri($hit_url);
        $url->setOptions([
          'attributes' => [
            'target' => '_blank',
          ],
        ]);
        $link = Link::fromTextAndUrl($hit->linkout_id, $url);
      }
      catch (\Exception $e) {
        // @todo user-friendly invalid config message here
        throw $e;
      }
    }
    return $link;
  }

  /**
   * Handle the 'jbrowse' linkout type.
   *
   * @param string $url_prefix
   *   The URL prefix for the BLAST Database queried.
   * @param object $hit
   *   The blast XML hit object. This object has the following keys based on the
   *   XML: Hit_num, Hit_id, Hit_def, Hit_accession, Hit_len and Hit_hsps.
   *   Furthermore, a linkout_id key has beek added that contains the part of
   *   the Hit_def extracted using a regex provided when the blastdb node was
   *   created.
   * @param array $info
   *   Additional information that may be useful in creating a link-out.
   *   This includes:
   *    - query_name: the name of the query sequence.
   *    - score: the score of the blast hit.
   *    - e-value: the e-value of the blast hit.
   * @param array $options
   *   Any additional options needed to determine the type of link-out.
   *
   * @return Link|null
   *   An html link if supported, or NULL if not.
   */
  protected function handleJbrowse(string $url_prefix, object $hit, array $info, array $options = []): ?Link {
    $link = NULL;
    return $link;
  }

}
