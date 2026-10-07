<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-user')]
class CreateUserCommand
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $userPaswwordHasher,
    ){}

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
    ){
        $helper = new QuestionHelper();

        $question = new Question('Enter an email : ');
        $email = $helper->ask($input, $output, $question);

        $question = new Question('Enter a password : ');
        $question->setHidden(true);
        $password = $helper->ask($input, $output, $question);

        $question = new Question('Confirm password : ');
        $question->setHidden(true);
        $confirmation = $helper->ask($input, $output, $question);

        if($password !== $confirmation) {
            $output->writeln('Passwords don\'t match.');
            return Command::FAILURE;
        }

        $question = new Question('Firstname : ');
        $firstname = $helper->ask($input, $output, $question);

        $question = new Question('Lastname : ');
        $lastname = $helper->ask($input, $output, $question);

        $question = new ChoiceQuestion('Choose a role : ', ['ROLE_ADMIN', 'ROLE_USER']);
        $question->setErrorMessage('Role is not in the list of possible choices.');
        $role = $helper->ask($input, $output, $question);

        $user = (new User())
            ->setEmail($email)
            ->setRoles([$role])
            ->setIsActive(true)
            ->setFirstname($firstname)
            ->setLastname($lastname);

        $user->setPassword($this->userPaswwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
