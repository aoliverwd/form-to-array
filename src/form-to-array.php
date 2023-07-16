<?php

// Set namespace
namespace FormToArray;

// Final parser class
final class Parser
{
    /**
     * Input attributes
     * @var array<string>
     */
    private array $input_attributes = ['name', 'id', 'type', 'value', 'required', 'placeholder'];

    /**
     * Form form_attributes_matches
     * @var array<mixed>
     */
    private array $form_attributes_matches = ['action', 'method'];

    /**
     * Form attributes
     * @var array<mixed>
     */
    private array $form_attributes = [];

    /**
     * Processed inputs
     * @var array<mixed>
     */
    private array $inputs = [];

    /**
     * Class constructor
     * @param string $working_string
     */
    public function __construct(private string $working_string = '')
    {
        // Set inputs
        $this->getInputs();
    }

    /**
     * Get inputs
     * @return void
     */
    public function getInputs(): void
    {
        // Find inputs and textareas
        if (preg_match_all('/\<input(.*?)\/>|\<input(.*?)\>|\<textarea(.*?)\>/s', $this->working_string, $matches)) {
            $this->processFoundInputs($matches);
        }
    }

    /**
     * Get Attributes
     * @param  array<mixed>  $attributes
     * @param  string  $type
     * @return void
     */
    public function setAttributes(array $attributes, string $type = 'input'): void
    {
        switch ($type) {
            case 'form':
                $this->form_attributes_matches = array_merge($this->form_attributes_matches, $attributes);
                break;
            default:
                $this->input_attributes = array_merge($this->input_attributes, $attributes);
        }
    }

    /**
     * Process inputs
     * @param  array<mixed>  $inputs
     * @return void
     */
    private function processFoundInputs(array $inputs): void
    {
        if (isset($inputs[0]) && count($inputs[0]) > 0) {
            $this->inputs = array_map(fn ($input_string) => $this->processInput($input_string), $inputs[0]);
        }

        // Filter empty nodes in array
        $this->inputs = array_filter($this->inputs);

        // Find form attributes
        $this->getFormAttributes();
    }

    /**
     * Process input
     * @param  string  $input_string
     * @return array<mixed>
     */
    private function processInput(string $input_string): array
    {
        $input_string = trim($input_string);

        // Get input type
        preg_match('/^\<(.*?) /', $input_string, $input_type_match);
        $input_type = isset($input_type_match[1]) ? trim($input_type_match[1]) : '';

        // Process attributes
        $return_input = $this->processAttributes($input_string, [], $this->input_attributes);

        // Find input label
        if (isset($return_input['id'])) {
            $return_input['label'] = $this->findLabel($return_input['id']);
        }

        // Add input type
        if (!empty($return_input)) {
            $return_input['input_type'] = $input_type;
        }

        return $return_input;
    }

    /**
     * Find input label
     * @param  string $input_id
     * @return string
     */
    private function findLabel(string $input_id): string
    {
        preg_match('/\<label(.*?)for="' . $input_id . '"(.*?)\>(.*?)\<\/label\>/s', $this->working_string, $match);
        return count($match) === 4 ? $match[3] : '';
    }

    /**
     * Find form attributes
     * @return void
     */
    private function getFormAttributes(): void
    {
        if (preg_match('/\<form(.*?)\>/s', $this->working_string, $match)) {
            if (isset($match[1]) && !empty(trim($match[1]))) {
                $this->form_attributes = $this->processAttributes($match[1], $this->form_attributes, $this->form_attributes_matches);
            }
        }
    }

    /**
     * Add attributes to item
     * @param  string $attributes_string
     * @param  array<mixed>  $item
     * @param  array<mixed>  $attribute_matches
     * @return array<mixed>
     */
    private function processAttributes(string $attributes_string, array $item, array $attribute_matches): array
    {
        preg_match_all('/([a-zA-Z-0-9]+)="(.*?)"/', trim($attributes_string), $matches);
        if (count($matches) == 3) {
            foreach ($matches[1] as $key => $value) {
                $value = trim($value);

                if (in_array($value, $attribute_matches) && isset($matches[2][$key])) {
                    $item[$value] = $matches[2][$key];
                }
            }
        }

        return $item;
    }

    /**
     * Compile output array
     * @return array<mixed>
     */
    public function compileOutputArray(): array
    {
        $input_types = [];
        foreach ($this->inputs as $input) {
            if (!isset($input_types[$input['input_type']])) {
                $input_types[$input['input_type']] = [];
            }

            if (!isset($input_types[$input['input_type']][$input['type']])) {
                $input_types[$input['input_type']][$input['type']] = [];
            }

            $input_types[$input['input_type']][$input['type']][] = $input;
        }

        return [
            'form' => $this->form_attributes,
            'input_types' => $input_types
        ];
    }

    /**
     * Export to JSON
     * @param  boolean $pretty_print
     * @return string
     */
    public function toJson(bool $pretty_print = true): string
    {
        return $pretty_print ? (string) json_encode($this->compileOutputArray(), JSON_PRETTY_PRINT) : (string) json_encode($this->compileOutputArray());
    }
}
