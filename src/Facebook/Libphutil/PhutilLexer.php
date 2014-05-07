<?php

namespace Facebook\Libphutil;

/**
 * Slow, inefficient regexp-based lexer. Define rules like this:
 *
 *   array(
 *     'start'  => array(...),
 *     'state1' => array(...),
 *     'state2' => array(...),
 *   )
 *
 * Lexers start at the state named 'start'. Each state should have a list of
 * rules which can match in that state. A list of rules looks like this:
 *
 *   array(
 *     array('\s+', 'space'),
 *     array('\d+', 'digit'),
 *     array('\w+', 'word'),
 *   )
 *
 * The lexer operates by processing each rule in the current state in order.
 * When one matches, it produces a token. For example, the lexer above would
 * lex this text:
 *
 *   3 asdf
 *
 * ...to produce these tokens (assuming the rules are for the 'start' state):
 *
 *   array('digit', '3', null),
 *   array('space', ' ', null),
 *   array('word', 'asdf', null),
 *
 * A rule can also cause a state transition:
 *
 *   array('zebra', 'animal', 'saw_zebra'),
 *
 * This would match the text "zebra", emit a token of type "animal", and change
 * the parser state to "saw_zebra", causing the lexer to start using the rules
 * from that state.
 *
 * To pop the lexer's state, you can use the special state '!pop'.
 *
 * Finally, you can provide additional options in the fourth parameter.
 * Supported options are `case-insensitive` and `context`.
 *
 * Possible values for `context` are `push` (push the token value onto the
 * context stack), `pop` (pop the context stack and use it to provide context
 * for the token), and `discard` (pop the context stack and throw away the
 * value).
 *
 * For example, to lex text like this:
 *
 *   Class::CONSTANT
 *
 * You can use a rule set like this:
 *
 *   'start' => array(
 *     array('\w+(?=::)', 'class', 'saw_class', array('context' => 'push')),
 *   ),
 *   'saw_class' => array(
 *     array('::', 'operator'),
 *     array('\w+', 'constant, '!pop', array('context' => 'pop')),
 *   ),
 *
 * This would parse the above text into this token stream:
 *
 *   array('class', 'Class', null),
 *   array('operator', '::', null),
 *   array('constant', 'CONSTANT', 'Class'),
 *
 * For a concrete implementation, see @{class:PhutilPHPFragmentLexer}.
 *
 * @task lexerimpl  Lexer Implementation
 * @task rule       Lexer Rules
 * @task tokens     Lexer Tokens
 *
 * @group lexer
 */
abstract class PhutilLexer {

  private $processedRules;
  private $lastState;


/* -(  Lexer Rules  )-------------------------------------------------------- */


  /**
   * Return a set of rules for this lexer. See description in
   * @{class:PhutilLexer}.
   *
   * @return dict Lexer rules.
   * @task lexerimpl
   */
  abstract protected function getRawRules();


/* -(  Lexer Rules  )-------------------------------------------------------- */


  /**
   * Process, normalize, and validate the raw lexer rules.
   *
   * @task rule
   */
  protected function getRules() {
    $class = get_class($this);

    $raw_rules = $this->getRawRules();

    if (!is_array($raw_rules)) {
      $type = gettype($raw_rules);
      throw new \UnexpectedValueException(
        "Expected {$class}->getRawRules() to return array, got {$type}.");
    }

    if (empty($raw_rules['start'])) {
      throw new \UnexpectedValueException(
        "Expected {$class} rules to define rules for state 'start'.");
    }

    $processed_rules = array();
    foreach ($raw_rules as $state => $rules) {

      if (!is_array($rules)) {
        $type = gettype($rules);
        throw new \UnexpectedValueException(
          "Expected list of rules for state '{$state}' in {$class}, got ".
          "{$type}.");
      }

      foreach ($rules as $key => $rule) {
        $n = count($rule);
        if ($n < 2 || $n > 4) {
          throw new \UnexpectedValueException(
            "Expected rule '{$key}' in state '{$state}' in {$class} to have ".
            "2-4 elements (regex, token, [next state], [options]), got {$n}.");
        }
        $rule = array_values($rule);
        if (count($rule) == 2) {
          $rule[] = null;
        }
        if (count($rule) == 3) {
          $rule[] = array();
        }

        foreach ($rule[3] as $option => $value) {
          switch ($option) {
            case 'context':
              if ($value !== 'push' &&
                  $value !== 'pop' &&
                  $value !== 'discard' &&
                  $value !== null) {
                throw new \UnexpectedValueException(
                  "Rule '{$key}' in state '{$state}' in {$class} has unknown ".
                  "context rule '{$value}', expected 'push', 'pop' or ".
                  "'discard'.");
              }
              break;
            default:
              throw new \UnexpectedValueException(
                "Rule '{$key}' in state '{$state}' in {$class} has unknown ".
                "option '{$option}'.");
          }
        }

        $flags = 'sS';

        // NOTE: The "\G" assertion is an offset-aware version of "^".
        $rule[0] = '(\\G'.$rule[0].')'.$flags;

        if (@preg_match($rule[0], '') === false) {
          $error = error_get_last();
          throw new \UnexpectedValueException(
            "Rule '{$key}' in state '{$state}' in {$class} defines an ".
            "invalid regular expression ('{$rule[0]}'): ".
            \Facebook\Libphutil\Functions\utils::idx($error, 'message'));
        }

        $next_state = $rule[2];
        if ($next_state !== null && $next_state !== '!pop') {
          if (empty($raw_rules[$next_state])) {
            throw new \UnexpectedValueException(
              "Rule '{$key}' in state '{$state}' in {$class} transitions to ".
              "state '{$next_state}', but there are no rules for that state.");
          }
        }

        $processed_rules[$state][] = $rule;
      }
    }

    return $processed_rules;
  }


/* -(  Lexer Tokens  )------------------------------------------------------- */


  /**
   * Lex an input string into tokens.
   *
   * @param string Input string.
   * @param string Initial lexer state.
   * @return list List of lexer tokens.
   * @task tokens
   */
  public function getTokens($input, $initial_state = 'start') {
    if (empty($this->processedRules)) {
      $this->processedRules = $this->getRules();
    }
    $rules = $this->processedRules;

    $this->lastState = null;

    $position = 0;
    $length   = strlen($input);

    $tokens = array();
    $states = array();
    $states[] = 'start';
    if ($initial_state != 'start') {
      $states[] = $initial_state;
    }
    $context = array();
    while ($position < $length) {
      $state_rules = \Facebook\Libphutil\Functions\utils::idx($rules, end($states), array());
      foreach ($state_rules as $rule) {

        $matches = null;
        if (!preg_match($rule[0], $input, $matches, 0, $position)) {
          continue;
        }

        list($regexp, $token_type, $next_state, $options) = $rule;

        $match_length = strlen($matches[0]);
        if (!$match_length) {
          if ($next_state === null) {
            throw new \UnexpectedValueException(
              "Rule '{$regexp}' matched a zero-length token and causes no ".
              "state transition.");
          }
        } else {
          $position += $match_length;
          $token = array($token_type, $matches[0]);

          $copt = \Facebook\Libphutil\Functions\utils::idx($options, 'context');
          if ($copt == 'push') {
            $context[] = $matches[0];
            $token[] = null;
          } else if ($copt == 'pop') {
            if (empty($context)) {
              throw new \UnexpectedValueException(
                "Rule '{$regexp}' popped empty context!");
            }
            $token[] = array_pop($context);
          } else if ($copt == 'discard') {
            if (empty($context)) {
              throw new \UnexpectedValueException(
                "Rule '{$regexp}' discarded empty context!");
            }
            array_pop($context);
            $token[] = null;
          } else {
            $token[] = null;
          }

          $tokens[] = $token;
        }

        if ($next_state !== null) {
          if ($next_state == '!pop') {
            array_pop($states);
            if (empty($states)) {
              throw new \UnexpectedValueException(
                "Rule '{$regexp}' popped off the last state.");
            }
          } else {
            $states[] = $next_state;
          }
        }

        continue 2;
      }

      throw new \UnexpectedValueException(
        "No lexer rule matched input at char {$position}.");
    }

    $this->lastState = $states;

    return $tokens;
  }


  /**
   * Merge adjacent tokens of the same type. For example, if a comment is
   * tokenized as <"//", "comment">, this method will merge the two tokens into
   * a single combined token.
   */
  public function mergeTokens(array $tokens) {
    $last = null;
    $result = array();
    foreach ($tokens as $token) {
      if ($last === null) {
        $last = $token;
        continue;
      }
      if (($token[0] == $last[0]) && ($token[2] == $last[2])) {
        $last[1] .= $token[1];
      } else {
        $result[] = $last;
        $last = $token;
      }
    }
    if ($last !== null) {
      $result[] = $last;
    }
    return $result;
  }

  public function getLexerState() {
    return $this->lastState;
  }

}
