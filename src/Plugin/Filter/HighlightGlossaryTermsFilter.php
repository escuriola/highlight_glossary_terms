<?php

namespace Drupal\highlight_glossary_terms\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Utility\Html;

/**
 * Provides a filter to restrict images to site.
 * @Filter(
 * id = "filter_muprespa_dictionay",
 * title = @Translation("FM Dictionay"),
 * description = @Translation("Highlight muprespa dictionary words"),
 * type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * weight = 9
 * )
 */
class HighlightGlossaryTermsFilter extends FilterBase implements ContainerFactoryPluginInterface {
  /**
   * An entity manager object.
   *
   * @var \Drupal\muprespa_dictionary\Builder
   */
  protected $builder;
  /**
   * Constructs a \Drupal\editor\Plugin\Filter\EditorFileReference object.
   *
   * @param array $configuration
   *         A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *         The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *         The plugin implementation definition.
   * @param \Drupal\muprespa_dictionary\Builder $builder
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Builder $builder) {
    $this->builder = $builder;
    parent::__construct ( $configuration, $plugin_id, $plugin_definition );
  }
  /**
   *
   * {@inheritdoc}
   *
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static ( $configuration, $plugin_id, $plugin_definition, $container->get ( 'muprespa_dictionary.builder' ) );
  }
  /**
   *
   * {@inheritdoc}
   *
   */
  public function process($text, $langcode) {
    return new FilterProcessResult ( $this->dictionaryProcess ( $text, $langcode ) );
  }
  /**
   *
   * {@inheritdoc}
   *
   */
  public function tips($long = FALSE) {
    return $this->t ( 'Highlight muprespa dictionary words.' );
  }

  /**
   *
   * Resaltamos palabras de tipo diccionario en el body de cualquier contenido.
   * $arrayTypesExclude: array con los tipos de contenido que excluimos para resaltar palabras.
   *
   */
  protected function dictionaryProcess($text, $langcode) {
    if (empty ( $text ) ) {
      return $text;
    }
    $node = \Drupal::routeMatch()->getParameter('node');
    if( $node && is_object($node) ){
      $typeName = $node->bundle();
      $arrayTypesExclude = ['nota_prensa'];
      if( in_array($typeName,$arrayTypesExclude) ){
        return $text;
      }
    }
    $dictionary = $this->builder->getDictionary ( $langcode );
    if (! empty ( $dictionary )) {
      foreach ( $dictionary as $nid => $word ) {
        $link = Link::fromTextAndUrl ( "$0", Url::fromRoute ( 'entity.node.canonical', [
          'node' => $nid
        ], [
          'attributes' => [
            'class' => [
              'use-ajax',
              'dictionary-word'
            ],
            'data-dialog-type' => 'modal'
          ]
        ] ) );
        //$word = str_replace(' ','&nbsp;',$word);
        $text = $this->highlight ( $word, $link->toString (), $text );
      }
    }
    $text = str_replace('***','&',$text);
    //return htmlspecialchars_decode($text);
    return $text;
  }

  protected function highlight($word, $replace, $html) {
    $html_dom = Html::load ( $html );
    $xpath = new \DOMXPath ( $html_dom );
    foreach ( $xpath->query ( '//text()[(parent::p)] | //text()[(parent::li)]' ) as $node ) {
      $f = $html_dom->createDocumentFragment ();
      $text = $this->highlightText ( $word, $replace, $node->nodeValue );
      $text = str_replace('&','***',$text);
      //$f->appendXML ( htmlspecialchars($text, ENT_HTML5, 'UTF-8', false) );
      $f->appendXML ( $text );
      $node->parentNode->replaceChild ( $f, $node );
    }
    return Html::serialize ( $html_dom );
  }

  private function highlightText($word, $replace, $text) {
    $p = preg_quote ( $word, '/' ); // The pattern to match
    return preg_replace ( "/\b($p)\b/i", $replace, $text );
  }

}
