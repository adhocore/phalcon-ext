<?php

namespace PhalconExt\Example;

use Phalcon\Cli\Task;

class MainTask extends Task
{
    public function onConstruct()
    {
        ($console = $this->console)
            ->command('main:main', 'MainTask')
                ->option('-n, --name <name>', 'Name')
                ->option('-a, --age [age]', 'Age', 'intval', 0)
                ->option('-h, --hobbies [...]', 'Hobbies')
                ->tap($console)
            ->command('main:run', 'MainTask@run', true)
                ->option('-c --config <path>', 'Config file')
                ->tap($console)
                ->schedule('@always')
            ->command('main:joke', 'A random joke for you', false)
                ->tap($console)
                // joke task doesnt need args/options and doesnt need to be scheduled
            ->command('main:interact', 'Interactive demo', false);
    }

    public function mainAction()
    {
        $this->interactor
            ->boldGreen('Hello from main:main!', true)
            ->bgRed('It allows known options only', true)->eol()
            ->comment('Name you entered is: ' . $this->command->name, true);
    }

    public function runAction()
    {
        // Options specified in ::onConstruct() above.
        $this->interactor
            ->boldGreen('Hello from main:run!', true)
            ->bgRed('It allows unknown options too', true)
            ->boldCyan('Input parameters:', true)
            ->comment(json_encode($this->command->values(), JSON_PRETTY_PRINT), true)->eol()
            ->bgPurple('Try running this command again with random arguments', true);
    }

    public function jokeAction()
    {
        $this->interactor->yellow('Fetching a joke', true);

        $curl = curl_init('https://icanhazdadjoke.com/');

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'User-Agent: CURL/' . PHP_VERSION,
            'Accept: text/plain',
        ]);

        $joke = curl_exec($curl);
        curl_close($curl);

        $this->interactor->comment($joke, true);
    }

    public function interactAction()
    {
        $confirm = $this->interactor->confirm('[confirm] Are you happy?', 'n');
        $confirm
            ? $this->interactor->greenBold('You are happy :)', true)
            : $this->interactor->redBold('You are sad :(', true);

        $fruits = ['a' => 'apple', 'b' => 'banana'];
        $choice = $this->interactor->choice('[choice] Select a fruit', $fruits, 'b');
        $this->interactor->greenBold("You selected: {$fruits[$choice]}", true);

        $fruits  = ['a' => 'apple', 'b' => 'banana', 'c' => 'cherry'];
        $choices = $this->interactor->choices('[choices] Select fruit(s)', $fruits, ['b', 'c']);
        $choices = \array_map(function ($c) use ($fruits) {
            return $fruits[$c];
        }, $choices);
        $this->interactor->greenBold('You selected: ' . implode(', ', $choices), true);

        $any = $this->interactor->prompt('[prompt] Anything', rand(1, 100));
        $this->interactor->greenBold("Anything is: $any", true);

        $nameValidator = function ($value) {
            if (\strlen($value) < 5) {
                throw new \Exception('Name should be atleast 5 chars');
            }

            return $value;
        };

        $name = $this->interactor->prompt('[prompt:validator] Name', null, $nameValidator, 3);
        $this->interactor->greenBold("The name is: $name", true);
    }
}
