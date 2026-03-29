<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Controller\Api;

use Danilovl\WebCommandBundle\Entity\{
    Command,
    History
};
use Danilovl\WebCommandBundle\Repository\HistoryRepository;
use Doctrine\Common\Collections\Order;
use Symfony\Component\HttpFoundation\{
    Request,
    JsonResponse
    
};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final readonly class HistoryController
{
    public function __construct(private HistoryRepository $historyRepository) {}

    #[Route(
        path: '/commands/{command}/histories',
        name: 'danilovl_web_command_histories',
        requirements: ['command' => Requirement::DIGITS],
        methods: ['GET'])
    ]
    public function list(Request $request, Command $command): JsonResponse
    {
        $page = $request->query->getInt('page', 1);

        $criteria = ['command' => $command];

        $limit = 10;
        $offset = ($page - 1) * $limit;

        $histories = $this->historyRepository->findBy(
            criteria: $criteria,
            orderBy: ['createdAt' => Order::Descending->value],
            limit: $limit,
            offset: $offset
        );
        $totalHistories = $this->historyRepository->count($criteria);

        $responseData = [
            'histories' => $histories,
            'totalPages' => (int) ceil($totalHistories / $limit),
            'currentPage' => $page
        ];

        return new JsonResponse($responseData);
    }

    #[Route(
        path: '/commands/histories/{id}',
        name: 'danilovl_web_command_history_detail',
        requirements: ['id' => Requirement::DIGITS],
        methods: ['GET'])
    ]
    public function get(History $history): JsonResponse
    {
        return new JsonResponse($history);
    }
}
