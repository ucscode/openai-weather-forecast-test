<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class Homepage
{
    #[Route('/', name: 'home')]

    public function entry()
    {
        return OpenAIResponse::failedResponse(Response::HTTP_BAD_REQUEST, "Bad Request");
    }

}
