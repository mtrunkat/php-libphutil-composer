<?php

namespace Facebook\Libphutil\Functions;

class execx {
  
  /**
   * Execute a command and capture stdout and stderr. If the command exits with
   * a nonzero error code, a @{class:CommandException} will be thrown. If you need
   * to manually handle error conditions, use @{function:exec_manual}.
   *
   *   list ($stdout, $stderr) = \Facebook\Libphutil\Functions\execx::execx('ls %s', $file);
   *
   * @param  string  sprintf()-style command pattern to execute.
   * @param  ...     Arguments to sprintf pattern.
   * @return array   List of stdout and stderr.
   * @group exec
   */
  static function execx($cmd /* , ... */) {
    $args = func_get_args();
    $future = \Facebook\Libphutil\Functions\utils::newv('\Facebook\Libphutil\ExecFuture', $args);
    return $future->resolvex();
  }
  
  
  /**
   * Execute a command and capture stdout, stderr, and the return value.
   *
   *   list ($err, $stdout, $stderr) = \Facebook\Libphutil\Functions\execx::exec_manual('ls %s', $file);
   *
   * When invoking this function, you must manually handle the error
   * condition. Error flows can often be simplified by using @{function:execx}
   * instead, which throws an exception when it encounters an error.
   *
   * @param  string  sprintf()-style command pattern to execute.
   * @param  ...     Arguments to sprintf pattern.
   * @return array   List of return code, stdout, and stderr.
   * @group exec
   */
  static function exec_manual($cmd /* , ... */) {
    $args = func_get_args();
    $ef = \Facebook\Libphutil\Functions\utils::newv('\Facebook\Libphutil\ExecFuture', $args);
    return $ef->resolve();
  }
  
  
  /**
   * Wrapper for @{class:PhutilExecPassthru}.
   *
   * @param  string  sprintf()-style command pattern to execute.
   * @param  ...     Arguments to sprintf pattern.
   * @return int     Return code.
   * @group exec
   */
  static function phutil_passthru($cmd /* , ... */) {
    $args = func_get_args();
    return \Facebook\Libphutil\Functions\utils::newv('\Facebook\Libphutil\PhutilExecPassthru', $args)->execute();
  }
  
  
  /**
   * Return a human-readable signal name (like "SIGINT" or "SIGKILL") for a given
   * signal number.
   *
   * @param   int     Signal number.
   * @return  string  Human-readable signal name.
   */
  static function phutil_get_signal_name($signo) {
  
    // These aren't always defined; try our best to look up the signal name.
    $constant_names = array(
      'SIGHUP',
      'SIGINT',
      'SIGQUIT',
      'SIGILL',
      'SIGTRAP',
      'SIGABRT',
      'SIGIOT',
      'SIGBUS',
      'SIGFPE',
      'SIGUSR1',
      'SIGSEGV',
      'SIGUSR2',
      'SIGPIPE',
      'SIGALRM',
      'SIGTERM',
      'SIGSTKFLT',
      'SIGCLD',
      'SIGCHLD',
      'SIGCONT',
      'SIGTSTP',
      'SIGTTIN',
      'SIGTTOU',
      'SIGURG',
      'SIGXCPU',
      'SIGXFSZ',
      'SIGVTALRM',
      'SIGPROF',
      'SIGWINCH',
      'SIGPOLL',
      'SIGIO',
      'SIGPWR',
      'SIGSYS',
      'SIGBABY',
    );
  
    $signal_names = array();
    foreach ($constant_names as $constant) {
      if (defined($constant)) {
        $signal_names[constant($constant)] = $constant;
      }
    }
  
    return \Facebook\Libphutil\Functions\utils::idx($signal_names, $signo);
  }
  
}
