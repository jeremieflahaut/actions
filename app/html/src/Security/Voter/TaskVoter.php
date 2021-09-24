<?php

namespace App\Security\Voter;

use App\Entity\Task;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class TaskVoter extends Voter
{
    protected function supports(string $attribute, $task): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, ['TASK_DELETE', 'TASK_EDIT', 'TASK_TOGGLE'])
            && $task instanceof Task;
    }

    protected function voteOnAttribute(string $attribute, $task, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case 'TASK_EDIT':
            case 'TASK_DELETE':
                return ($user === $task->getUser()) || (in_array('ROLE_ADMIN', $user->getRoles())
                        && null === $task->getUser());
            case 'TASK_TOGGLE':
                return $user === $task->getUser() || in_array('ROLE_ADMIN', $user->getRoles());
        }

        return false;
    }
}