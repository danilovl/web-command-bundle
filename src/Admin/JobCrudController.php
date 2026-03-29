<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Admin;

use Danilovl\WebCommandBundle\Entity\Job;
use Doctrine\Common\Collections\Order;
use EasyCorp\Bundle\EasyAdminBundle\Config\{
    Crud,
    Action,
    Actions
};
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\{
    AssociationField,
    ChoiceField,
    DateTimeField,
    IntegerField,
    CodeEditorField
};

/**
 * @extends AbstractCrudController<Job>
 */
class JobCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Job::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Job')
            ->setEntityLabelInPlural('Jobs')
            ->setDefaultSort(['id' => Order::Descending->value]);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('id', 'ID')->onlyOnIndex();
        yield AssociationField::new('command', 'Command');
        yield ChoiceField::new('status', 'Status')
            ->setChoices([
                'Queued' => 'queued',
                'Running' => 'running',
                'Completed' => 'completed',
                'Failed' => 'failed',
            ])
            ->renderAsBadges([
                'queued' => 'secondary',
                'running' => 'primary',
                'completed' => 'success',
                'failed' => 'danger',
            ]);

        yield CodeEditorField::new('input', 'Input')
            ->setLanguage('javascript')
            ->formatValue(static function (array $value): string {
                return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]';
            });

        yield DateTimeField::new('createdAt', 'Created at');
    }
}
