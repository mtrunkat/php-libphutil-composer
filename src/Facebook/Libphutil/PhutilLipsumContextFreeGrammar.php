<?php

namespace Facebook\Libphutil;

class PhutilLipsumContextFreeGrammar
  extends \Facebook\Libphutil\PhutilContextFreeGrammar {

  protected function getRules() {
    return array(
      'start' => array(
        '[words].',
        '[words].',
        '[words].',
        '[words]: [word], [word], [word] [word].',
        '[words]; [lowerwords].',
        '[words]!',
        '[words], "[words]."',
        '[words] ("[upperword] [upperword] [upperword]") [lowerwords].',
        '[words]?',
      ),
      'words' => array(
        '[upperword] [lowerwords]',
      ),
      'upperword' => array(
        'Lorem',
        'Ipsum',
        'Dolor',
        'Sit',
        'Amet',
      ),
      'lowerwords' => array(
        '[word]',
        '[word] [word]',
        '[word] [word] [word]',
        '[word] [word] [word] [word]',
        '[word] [word] [word] [word] [word]',
        '[word] [word] [word] [word] [word]',
        '[word] [word] [word] [word] [word] [word]',
        '[word] [word] [word] [word] [word] [word]',
      ),
      'word' => array(
        'ad',
        'adipisicing',
        'aliqua',
        'aliquip',
        'amet',
        'anim',
        'aute',
        'cillum',
        'commodo',
        'consectetur',
        'consequat',
        'culpa',
        'cupidatat',
        'deserunt',
        'do',
        'dolor',
        'dolore',
        'duis',
        'ea',
        'eiusmod',
        'elit',
        'enim',
        'esse',
        'est',
        'et',
        'eu',
        'ex',
        'excepteur',
        'exercitation',
        'fugiat',
        '\Facebook\Libphutil\Functions\utils::id',
        'in',
        'incididunt',
        'ipsum',
        'irure',
        'labore',
        'laboris',
        'laborum',
        'lorem',
        'magna',
        'minim',
        'mollit',
        'nisi',
        'non',
        'nostrud',
        'nulla',
        'occaecat',
        'officia',
        'pariatur',
        'proident',
        'qui',
        'quis',
        'reprehenderit',
        'sed',
        'sint',
        'sit',
        'sunt',
        'tempor',
        'ullamco',
        'ut',
        'velit',
        'veniam',
        'voluptate',
      ),
    );
  }

}
