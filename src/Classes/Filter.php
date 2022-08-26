<?php

namespace App\Classes;

use App\Constants\ApplicationConstants;
use App\Traits\Classes\HasEntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use JetBrains\PhpStorm\Internal\TentativeType;
use JsonSerializable;
use Symfony\Component\Routing\RouterInterface;

class Filter implements JsonSerializable
{
    use HasEntityManager;

    public const NONE = 'NONE';
    public const AND = 'AND';
    public const OR = 'OR';
    public const LIST_AND = 'LIST_AND';
    public const LIST_OR = 'LIST_OR';
    public const BETWEEN = 'BETWEEN';
    public const CUSTOM = 'CUSTOM';
    public const DATE_TYPE = 'date';
    public const PARENT_CHILD_TYPE = 'parentChild';
    public const SOFT_DELETED_FIELD = 'softDeleted';
    private string $label;
    private string $name;
    private ?string $url = null;
    private array $dependants = [];
    private string|array|null $values = [];
    private bool $multiple = false;
    private ?string $type = null;
    private ?object $options = null;
    private ?\Closure $custom = null;
    private string|array|null $field = null;
    private ?string $expression;
    private array $data = [];
    private ?string $session = null;
    private ?string $group = null;
    private ?string $groupExpression = null;

    public const DATE_YESTERDAY = [
        'name' => 'Yesterday',
        'value' => -1,
    ];
    public const DATE_TODAY = [
        'name' => 'Today',
        'value' => 0,
    ];
    public const DATE_LAST_WEEK = [
        'name' => 'Last Week',
        'value' => -7,
    ];
    public const DATE_LAST_30_DAYS = [
        'name' => 'Last 30 Days',
        'value' => -30,
    ];
    public const DATE_LAST_180_DAYS = [
        'name' => 'Last 180 Days',
        'value' => -180,
    ];
    public const DATE_LAST_YEAR = [
        'name' => 'Last Year',
        'value' => -365,
    ];
    public const DATE_LAST_TWO_YEARS = [
        'name' => 'Last 2 Years',
        'value' => -730,
    ];
    public const DATE_TOMORROW = [
        'name' => 'Tomorrow',
        'value' => 1,
    ];
    public const DATE_NEXT_WEEK = [
        'name' => 'Next Week',
        'value' => 7,
    ];
    public const DATE_NEXT_30_DAYS = [
        'name' => 'Next 30 Days',
        'value' => 30,
    ];
    public const DATE_NEXT_180_DAYS = [
        'name' => 'Next 180 Days',
        'value' => 180,
    ];
    public const DATE_NEXT_CALENDAR_MONTH = [
        'name' => 'Next Month',
        'value' => '+1 Calendar Month',
    ];
    public const DATE_NEXT_3_CALENDAR_MONTHS = [
        'name' => 'Next 3 Months',
        'value' => '+3 Calendar Month',
    ];
    public const DATE_EMPTY = [
        'name' => 'Clear',
        'value' => null,
    ];
    public const DATE_PREVIOUS_PERIOD = [
        'name' => 'Previous Period',
        'value' => 'Previous Period',
    ];
    public const DATE_PREVIOUS_30_DAYS = [
        'name' => 'Previous 30 Days',
        'value' => 'Previous 30 Days',
    ];
    public const DATE_PREVIOUS_CALENDAR_MONTH = [
        'name' => 'Previous Month',
        'value' => '-1 Calendar Month',
    ];
    public const DATE_PREVIOUS_3_CALENDAR_MONTHS = [
        'name' => 'Previous 3 Months',
        'value' => '-3 Calendar Month',
    ];
    public const DATE_PREVIOUS_180_DAYS = [
        'name' => 'Previous 180 Days',
        'value' => 'Previous 180 Days',
    ];
    public const DATE_PREVIOUS_YEAR = [
        'name' => 'Previous Year',
        'value' => 'Previous Year',
    ];

    public function __construct(
        ?string $label = null,
        ?string $name = null,
        ?string $url = null,
        ?array $dependants = null,
        ?array $values = null,
        ?bool $multiple = null,
        ?string $type = null,
        ?object $options = null
    ) {
        $this->label = $label ?? $this->label;
        $this->name = $name ?? $this->name;
        $this->url = $url ?? $this->url;
        $this->dependants = $dependants ?? $this->dependants;
        $this->values = $values ?? $this->values;
        $this->multiple = $multiple ?? $this->multiple;
        $this->type = $type ?? $this->type;
        $this->options = $options ?? $this->options;
        if (!$this->multiple) {
            $this->values = null;
        }
    }

    public static function createFilter(
        ?string $label = null,
        ?string $name = null,
        ?string $url = null,
        ?array $dependants = null,
        ?array $values = null,
        ?bool $multiple = null,
        ?string $type = null,
        ?object $options = null
    ): Filter {
        return new Filter(
            $label,
            $name,
            $url,
            $dependants,
            $values,
            $multiple,
            $type,
            $options
        );
    }

    public function getLabel(): string {
        return $this->label;
    }

    public function setLabel(string $label): self {
        $this->label = $label;
        return $this;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): self {
        $this->name = $name;
        return $this;
    }

    public function getUrl(): string {
        return $this->url;
    }

    public function setUrl(string $url): self {
        $this->url = $url;
        return $this;
    }

    public function getDependants(): array {
        return $this->dependants;
    }

    public function setDependants($dependants): self {
        $this->dependants = $dependants;
        return $this;
    }

    public function addDependant($dependant): self {
        $this->dependants[] = $dependant;
        return $this;
    }

    public function getValues() {
        return $this->values;
    }

    public function setValues($values): self {
        $this->values = $values;
        return $this;
    }

    public function getMultiple(): bool {
        return $this->multiple;
    }

    public function setMultiple(bool $multiple): self {
        $this->multiple = $multiple;
        if (!$this->multiple) {
            $this->values = empty($this->values) ? null : $this->values;
        } else {
            $this->values = empty($this->values) ? [] : $this->values;
        }
        return $this;
    }

    public function getType(): ?string {
        return $this->type;
    }

    public function setType(?string $type): self {
        $this->type = $type;
        if ($type === self::DATE_TYPE && empty($this->values)) {
            $this->values = self::getBasicDateStartAndEnd();
        }
        return $this;
    }

    public function getOptions(): ?object {
        return $this->options;
    }

    public function setOptions($options): self {
        $this->options = is_array($options) ? (object)$options : $options;
        if (isset($this->options->range) && $this->options->range === true) {
            $this->multiple = true;
        }
        return $this;
    }

    public function getField(): array|string|null {
        return $this->field;
    }

    public function setField(string|array|null $field): self {
        $this->field = $field;
        return $this;
    }

    public function getExpression(): ?string {
        return $this->expression;
    }

    public function setExpression(?string $expression): self {
        $this->expression = $expression;
        return $this;
    }

    public function getCustom(): ?\Closure {
        return $this->custom;
    }

    public function setCustom(?\Closure $custom): self {
        $this->custom = $custom;
        return $this;
    }

    public function getData(): array {
        return $this->data;
    }

    public function setData(array $data): Filter {
        $this->data = $data;
        return $this;
    }

    public function getSession(): ?string {
        return $this->session;
    }

    public function setSession(?string $session): Filter {
        $this->session = $session;
        return $this;
    }

    public function getGroup(): ?string {
        return $this->group;
    }

    public function setGroup(?string $group): Filter {
        $this->group = $group;
        return $this;
    }

    public function getGroupExpression(): ?string {
        return $this->groupExpression;
    }

    public function setGroupExpression(?string $groupExpression): Filter {
        $this->groupExpression = $groupExpression;
        return $this;
    }

    public static function getBasicDateStartAndEnd(): array {
        return [
            date('Y-m-d 00:00:00', strtotime('-30 days')),
            date('Y-m-d 23:59:59', time()),
        ];
    }

    public static function getLastYearStartAndEnd(): array {
        return [
            date('Y-m-d 00:00:00', strtotime('-365 days')),
            date('Y-m-d 23:59:59', time()),
        ];
    }

    public static function getReverseBasicDateStartAndEnd(): array {
        return [
            date('Y-m-d  00:00:00', time()),
            date('Y-m-d  23:59:59', strtotime('+30 days')),
        ];
    }

    public static function getBasicDateRangesPastAndFuture(): array {
        return [
            self::DATE_LAST_180_DAYS,
            self::DATE_LAST_30_DAYS,
            self::DATE_LAST_WEEK,
            self::DATE_YESTERDAY,
            self::DATE_TODAY,
            self::DATE_TOMORROW,
            self::DATE_NEXT_WEEK,
            self::DATE_NEXT_30_DAYS,
            self::DATE_NEXT_180_DAYS,
        ];
    }

    public static function getBasicDateRangesPastAndFutureCalendarMonths(): array {
        return [
            self::DATE_PREVIOUS_3_CALENDAR_MONTHS,
            self::DATE_PREVIOUS_CALENDAR_MONTH,
            self::DATE_LAST_WEEK,
            self::DATE_YESTERDAY,
            self::DATE_TODAY,
            self::DATE_TOMORROW,
            self::DATE_NEXT_WEEK,
            self::DATE_NEXT_CALENDAR_MONTH,
            self::DATE_NEXT_3_CALENDAR_MONTHS,
        ];
    }

    public static function getBasicDateRangesPast(): array {
        return [
            self::DATE_TODAY,
            self::DATE_YESTERDAY,
            self::DATE_LAST_WEEK,
            self::DATE_LAST_30_DAYS,
            self::DATE_PREVIOUS_CALENDAR_MONTH,
            self::DATE_LAST_180_DAYS,
            self::DATE_PREVIOUS_3_CALENDAR_MONTHS,
        ];
    }

    public static function getMonthRangesPast(): array {
        return [
            self::DATE_PREVIOUS_CALENDAR_MONTH,
            self::DATE_PREVIOUS_3_CALENDAR_MONTHS,
        ];
    }

    public static function getMonthRangesFuture(): array {
        return [
            self::DATE_NEXT_CALENDAR_MONTH,
            self::DATE_NEXT_3_CALENDAR_MONTHS,
        ];
    }

    public static function getBasicReportComparisonDateRangesPast($includeEmpty = false, $includeComparisonDates = false): array {
        $dates = [
        ];
        if ($includeComparisonDates === true) {
            $dates = array_merge($dates, [self::DATE_PREVIOUS_PERIOD, self::DATE_PREVIOUS_30_DAYS, self::DATE_PREVIOUS_180_DAYS, self::DATE_PREVIOUS_YEAR]);
        }
        if ($includeEmpty === true) {
            $dates = array_merge($dates, [self::DATE_EMPTY]);
        }
        return $dates;
    }


    /**
     * @return array
     * @author Orestes Sebele <orestes@socialplaces.io>
     * @since 2021-03-16
     */
    public static function getFullDateRangesPast(): array {
        return array_merge(self::getBasicDateRangesPast(), [
            self::DATE_LAST_YEAR,
            self::DATE_LAST_TWO_YEARS
        ]);
    }

    /**
     * @return array[]
     * @author Ranvir Maharaj <ranvir@socialplaces.io>
     * @since 2020-09-02
     */
    public static function getBasicDateRangesFuture(): array {
        return [
            self::DATE_TODAY,
            self::DATE_TOMORROW,
            self::DATE_NEXT_WEEK,
            self::DATE_NEXT_30_DAYS,
            self::DATE_NEXT_180_DAYS,
        ];
    }

    /**
     * @return array
     */
    public function toArray(): array {
        return [
            'label' => $this->label,
            'name' => $this->name,
            'url' => $this->url,
            'dependants' => $this->dependants,
            'values' => $this->values,
            'multiple' => $this->multiple,
            'type' => $this->type,
            'options' => $this->options,
            'data' => $this->data,
            'session' => $this->getSession(),
        ];
    }

    /**
     * @param string $label
     * @param string $name
     * @param string|null $session
     * @param string|null $url
     * @return Filter

     */
    public static function getSimpleYesNoFilter(string $label, string $name, string $session = null, string $url = null): Filter {
        $filter = self::createFilter($label, $name, $url)
            ->setData([
                    ['id' => ApplicationConstants::YES, 'name' => ApplicationConstants::YES_TEXT],
                    ['id' => ApplicationConstants::NO, 'name' => ApplicationConstants::NO_TEXT]
                ]
            );
        if ($session) {
            $filter->setSession($session);
        }
        return $filter;
    }

    /**
     * @param Filter $filter
     * @param $fieldOptions
     * @param null $filterName
     * @return Filter
     */
    public static function applyFields(Filter $filter, $fieldOptions, $filterName = null): Filter {
        if (!empty($fieldOptions)) {
            $fieldOptions = $fieldOptions[$filterName] ?? $fieldOptions;
            foreach ($fieldOptions as $method => $value) {
                $setter = sp_setter($method);
                if (method_exists(self::class, $setter)) {
                    $filter->$setter($value);
                }
            }
        }
        return $filter;
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }
}
