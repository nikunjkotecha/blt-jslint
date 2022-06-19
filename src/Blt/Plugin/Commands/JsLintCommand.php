<?php

namespace Acquia\BltJsLint\Blt\Plugin\Commands;

use Acquia\Blt\Robo\BltTasks;
use Acquia\Blt\Robo\Exceptions\BltException;
use Consolidation\AnnotatedCommand\CommandData;

/**
 * Defines commands related to JS Linting.
 */
class JsLintCommand extends BltTasks {

  /**
   * This will be called post `git:pre-commit` command is executed.
   *
   * @hook post-command internal:git-hook:execute:pre-commit
   */
  public function postGitPreCommit($result, CommandData $commandData) {
    $arguments = $commandData->arguments();
    if (!empty($arguments['changed_files'])) {
      $this->invokeCommand('validate:jslint:files', ['file_list' => $arguments['changed_files']]);
    }
  }

  /**
   * Setup
   *
   * @hook post-command source:build:frontend-reqs
   */
  public function jsSetup() {
    if (!$this->getConfigValue('js-lint')) {
      return;
    }

    $repo_root = $this->getConfigValue('repo.root');

    // Try to get the docroot directory name.
    $docroot = $this->getConfigValue('docroot');
    $docroot_dir = str_replace($repo_root, '', $docroot);
    $docroot_dir = trim($docroot_dir, '/\\');

    $result = $this->taskExecStack()
      ->dir($repo_root)
      ->exec('npm install eslint eslint-config-drupal eslint-plugin-yml --save-dev')
      ->printMetadata(FALSE)
      ->run();

    if ($result->getExitCode()) {
      throw new \Exception('Unable to setup eslint, please confirm node.js, npm, and npx are available.');
    }

    $template = file_get_contents(__DIR__ . '/../../../../template/eslintignore.dist');
    $template = explode(PHP_EOL, $template);

    if (file_exists("$repo_root/.eslintignore")) {
      $this->say('.eslintignore exists, we will merge from template.');
      $existing = file_get_contents("$repo_root/.eslintignore");
      $existing = array_filter(explode(PHP_EOL, $existing));
    }
    else {
      $this->say('.eslintignore doest not exist, we will copy from template.');
      $existing = [];
    }

    foreach ($template as $row) {
      $row = str_replace('docroot/', "$docroot_dir/", $row);
      if (array_search($row, $existing) === FALSE) {
        $existing[] = $row;
      }
    }

    $existing = array_filter($existing);

    // Add empty line in the end.
    $existing[] = '';

    file_put_contents("$repo_root/.eslintignore", implode(PHP_EOL, $existing));

    $template = file_get_contents(__DIR__ . '/../../../../template/eslintrc.json.dist');
    $template = json_decode($template, TRUE);

    if (file_exists("$docroot/.eslintrc.json")) {
      $existing = file_get_contents("$docroot/.eslintrc.json");
      $existing = json_decode($existing, TRUE);

      $this->say('.eslintrc.json exists, we will merge from template.');
    }
    else {
      $this->say('.eslintrc.json doest not exist, we will copy from template.');
      $existing = [];
    }

    $existing = is_array($existing) ? $existing : [];

    foreach ($template['globals'] as $rule_key => $rule) {
      $existing['globals'][$rule_key] = $rule;
    }

    foreach ($template['rules'] as $rule_key => $rule) {
      $existing['rules'][$rule_key] = $rule;
    }

    file_put_contents("$docroot/.eslintrc.json", json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
  }

  /**
   * Executes PHP Code Sniffer against configured files.
   *
   * By default, these include custom themes, modules, and tests.
   * Exclude files via .eslintignore in the project root directory.
   *
   * @command validate:js
   */
  public function sniffFileSets() {
    $exit_code = $this->doSniff();
    if ($exit_code) {
      throw new BltException('JS Linting failed.');
    }
  }

  /**
   * Executes JS Lint against a list of files.
   *
   * This command will execute JS Lint against a list of files. Note
   * that files excluded by .eslintignore will not be sniffed, even if
   * specifically included here.
   *
   * @param string $file_list
   *   A list of files to scan, separated by \n.
   *
   * @command validate:jslint:files
   *
   * @return int
   *   Exit code.
   */
  public function sniffFileList(string $file_list): int {
    $this->say('Sniffing changed files...');

    // Convert files list to array.
    $files = explode(PHP_EOL, $file_list);

    $files = array_filter($files, function ($file) {
        return $file && substr($file, -3) === '.js';
    });

    $return_exit_code = 0;

    foreach ($files ?? [] as $file) {
      // Fail even if one of the files failed.
      if ($this->doSniff($file)) {
        $return_exit_code = 1;
      }
    }

    return $return_exit_code;
  }

  /**
   * Executes PHP Code Sniffer using specified options/arguments.
   *
   * @param string $arguments
   *   The command arguments/options.
   *
   * @return int
   *   Exit code.
   */
  protected function doSniff(string $arguments = 'docroot') {
    if (!$this->getConfigValue('js-lint')) {
      return;
    }

    $command = $this->getConfigValue('repo.root') . '/node_modules/.bin/eslint';
    $command = "$command $arguments";

    if ($this->output()->isVerbose()) {
      $command .= ' -v';
    }
    elseif ($this->output()->isVeryVerbose()) {
      $command .= ' -vv';
    }

    $result = $this->taskExecStack()
      ->dir($this->getConfigValue('repo.root'))
      ->exec($command)
      ->printMetadata(FALSE)
      ->run();

    return $result->getExitCode();
  }

}
