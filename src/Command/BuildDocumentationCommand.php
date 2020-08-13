<?php

namespace EclipseGc\DocBuilder\Command;

use EclipseGc\CommonConsole\PlatformInterface;
use EclipseGc\CommonConsole\ProcessRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class BuildDocumentationCommand extends Command {

  protected static $defaultName = 'docs:build';

  /**
   * @var \EclipseGc\CommonConsole\ProcessRunner
   */
  protected $runner;

  public function __construct(ProcessRunner $runner, string $name = NULL) {
    $this->runner = $runner;
    parent::__construct($name);
  }

  protected function configure() {
    $this->setDescription("Compile documentation spread across multiple repos into a single directory structure.")
      ->addOption(
        'file_path',
        null,
        InputOption::VALUE_REQUIRED,
        'The file path of a json document describing which repos to search for documentation.'
      );
  }


  protected function execute(InputInterface $input, OutputInterface $output) {
    $file_path = $input->getOption('file_path');
    if (!file_exists($file_path)) {
      throw new \Exception(sprintf("The specified file \"%s\" does not exist. Please reference a json file that enumerates repositories from which to extract documentation.", $file_path));
    }
    $contents = file_get_contents($file_path);
    // @todo validate contents.
    $contents = json_decode($contents);
    if (empty($contents->repositories)) {
      throw new \Exception("No repositories found within the specified file.");
    }
    if (empty($contents->destination)) {
      throw new \Exception("No destination directory specified. Please add a top level \"destination\" element to your specified file with the destination directory where you'd like the documentation to be built.");
    }
    $destination = realpath($contents->destination);
    if (!$destination) {
      throw new \Exception(sprintf("The specified destination directory: \"$destination\" does not exist. Please create the directory before running this command."));
    }
    $this->prepDirectory($destination);
    foreach ($contents->repositories as $name => $repository) {
      $directory = $destination . DIRECTORY_SEPARATOR . "component";
      $process = Process::fromShellCommandline("cd $directory; git clone {$repository->repo} $name;");
      $this->runner->run($process, $this->getMockPlatform(), $output);
      $docs_dir = $directory . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . $repository->source;
      if (!file_exists($docs_dir)) {
        throw new \Exception(sprintf("The expected documentation directory of: \"%s\" was not found.", $docs_dir));
      }
      $destination_dir = $destination . DIRECTORY_SEPARATOR . "build" . DIRECTORY_SEPARATOR . $repository->destination;
      if (!file_exists($destination_dir)) {
        mkdir($destination_dir, 0777, TRUE);
      }
      $docs = $docs_dir . DIRECTORY_SEPARATOR . '*';
      $process = Process::fromShellCommandline("cp -r $docs $destination_dir; rm -Rf $directory");
      $this->runner->run($process, $this->getMockPlatform(), $output);
      mkdir($directory);
    }
  }

  protected function prepDirectory(string $destination) {
    $dirs = [
      'build',
      'component'
    ];
    foreach ($dirs as $dir) {
      if (!file_exists($destination . DIRECTORY_SEPARATOR . "$dir")) {
        mkdir($destination . DIRECTORY_SEPARATOR . "$dir");
      }
    }
  }

  private function getMockPlatform() {
    return new class() implements PlatformInterface {

      public static function getQuestions() {}

      public static function getPlatformId(): string {
        return 'MOCK';
      }

      public function getAlias(): string {
        return 'mock';
      }

      public function execute(Command $command, InputInterface $input, OutputInterface $output): void {}

      public function out(Process $process, OutputInterface $output, string $type, string $buffer): void {}

      public function get(string $key) {}

      public function set(string $key, $value) {}

      public function export(): array {}

      public function save(): PlatformInterface {
        return $this;
      }
    };
  }

}
