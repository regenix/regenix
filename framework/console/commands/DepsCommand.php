<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dim-s
 * Date: 23.04.13
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */

namespace framework\console\commands;

use framework\console\ConsoleCommand;
use framework\deps\ConnectException;
use framework\deps\DependencyDownloadException;
use framework\deps\DependencyNotFoundException;
use framework\deps\Origin;
use framework\deps\Repository;

class DepsCommand extends ConsoleCommand {

    const GROUP = 'deps';

    /** @var Repository */
    protected $repository;

    public function onBefore(){
        $this->project->loadDeps();
        $this->repository = new Repository($this->project->deps);
    }

    protected function renderDeps($label, $env, $deps){
        $this->writeln('    %s:', $label);
        $this->writeln();
        if(sizeof($deps)){
            $this->repository->setEnv($env);
            foreach($deps as $group => $dep){
                $find = $this->repository->findLocalVersion($group, $dep['version']);
                $status = 'ok';
                if (!$find)
                    $status = 'not found';
                else {
                    if (!$this->repository->isValid($group, $find['version'])){
                        $status = 'invalid';
                    }
                }

                $this->writeln('        - %s %s (use v%s) [%s]', $group, $dep['version'], $find ? $find['version'] : '?', $status);
            }
        } else {
            $this->writeln('        * empty');
        }

        $this->writeln();
    }

    public function __default(){

        if($this->args->has(0)){
            $sub = 'sub_' . $this->args->get(0);
            $this->{$sub}();
            return;
        }

        $this->writeln('Dependencies of `%s`:', $this->project->getName());
        $this->writeln();

        $this->renderDeps('Assets', 'assets', $this->project->deps['assets']);
        $this->writeln();
        $this->renderDeps('Modules', 'modules', $this->project->deps['modules']);
    }

    protected function update($env, $group, $dep, $step = 0){
        try {
            $this->write(($step ? '  try '.$step.': ' : '').'update `%s/%s/%s`', $env, $group, $dep['version']);
            $local  = $this->repository->findLocalVersion($group, $dep['version']);
            $result = $this->repository->download($group, $dep['version'], $this->opts->getBoolean('force'));

            if ($local && $local['version'] != $result['version'])
                $this->writeln('[ok upgrade, %s -> %s]', $local['version'], $result['version']);
            else
                $this->writeln('[ok, %s %s]', $result['version'], $result['skip'] ? 'skip' : 'downloaded');

        } catch (DependencyNotFoundException $e){
            $this->writeln('[error, not found]');
        } catch (DependencyDownloadException $e){
            $this->writeln('[error, can`t download], repeat ->');
            if ($step < 3)
                $this->update($env, $group, $dep, $step += 1);
            else
                throw $e;
        } catch (ConnectException $e){
            $this->writeln('[error, can`t connect]');
            if ($step < 2)
                $this->update($env, $group, $dep, $step += 1);
            else
                throw $e;
        }
    }

    protected function sub_update(){
        $variants = array('assets', 'modules');
        if ($this->args->has(1)){
            $variant = $this->args->get(1);
            if (!in_array($variant, $variants, true)){
                $this->writeln('Can`t find `%s` group dependencies', $variant);
                return;
            }
        }

        try {
            foreach($variants as $env){
                if ($variant){
                    if ($variant !== $env){
                        continue;
                    }
                }

                $this->repository->setEnv($env);
                $deps = $this->project->deps[$env];

                foreach((array)$deps as $group => $dep){
                    $this->update($env, $group, $dep);
                }
                $this->writeln();
            }
            $this->writeln('[OK] All updates are downloaded successfully.');
        } catch (\Exception $e){
            $this->writeln();
            $this->writeln('[ERROR] Can`t update dependencies.');
            $this->writeln('    Try again run `update` or `update -force` command');
            $this->writeln();
            $this->writeln('Last exception: %s', $e->getMessage());
        }
    }
}