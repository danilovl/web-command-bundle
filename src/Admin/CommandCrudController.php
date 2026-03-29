<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Admin;

use Danilovl\WebCommandBundle\Entity\Command;
use Doctrine\Common\Collections\Order;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\{
    TextField,
    BooleanField,
    TextareaField,
    DateTimeField,
    IntegerField,
    CodeEditorField
};

/**
 * @extends AbstractCrudController<Command>
 */
class CommandCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Command::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Command')
            ->setEntityLabelInPlural('Commands')
            ->setSearchFields(['id', 'name', 'description'])
            ->setDefaultSort(['id' => Order::Descending->value]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('id', 'ID')->onlyOnIndex();
        yield TextField::new('name', 'Name');
        yield TextField::new('command', 'Command')->setHelp('The actual console command, e.g. app:example');
        yield BooleanField::new('active', 'Active');
        yield BooleanField::new('async', 'Async');
        yield BooleanField::new('saveHistory', 'Save history');
        yield BooleanField::new('saveOutput', 'Save history output');

        yield BooleanField::new('allowCustomParameters', 'Allow custom parameters')
            ->setHelp('If false, users cannot add additional parameters when running the command');

        yield TextField::new('voterClass', 'Voter class/attribute')
            ->setHelp('Namespace or attribute for permission check (using symfony authorization checker)');

        yield CodeEditorField::new('parameters', 'Parameters')
            ->setLanguage('javascript')
            ->setHelp('Format: JSON array of strings. Example: ["--fix", "arg1"]')
            ->formatValue(static function (array $value): string {
                return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]';
            })
            ->setFormTypeOptions([
                'getter' => static function (Command $command): string {
                    return json_encode($command->getParameters(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]';
                },
                'setter' => static function (Command $command, ?string $value): void {
                    $parameters = json_decode($value ?? '[]', true);
                    if (is_array($parameters)) {
                        /** @var string[] $parameters */
                        $command->setParameters($parameters);
                    }
                },
            ]);

        yield TextareaField::new('description', 'Description');

        yield DateTimeField::new('createdAt', 'Created at')
            ->hideOnForm()
            ->setDisabled();

        yield DateTimeField::new('updatedAt', 'Updated at')
            ->hideOnForm()
            ->setDisabled();
    }
}
