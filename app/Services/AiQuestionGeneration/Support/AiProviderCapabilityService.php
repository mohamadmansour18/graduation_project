<?php

namespace App\Services\AiQuestionGeneration\Support;

use Illuminate\Support\Facades\Log;

class AiProviderCapabilityService
{
    /**
     * @param array<int, string> $providerNames
     * @return array<int, string>
     */
    public function filterProviderChain(array $providerNames, string $sourceType, array $context = []): array
    {
        return $this->filterProviderChainWithDetails(
            providerNames: $providerNames,
            sourceType: $sourceType,
            context: $context
        )['provider_chain'];
    }

    /**
     * @param array<int, string> $providerNames
     * @param array<string, mixed> $context
     * @return array{
     *     provider_chain: array<int, string>,
     *     accepted_providers: array<int, array<string, mixed>>,
     *     skipped_providers: array<int, array<string, mixed>>
     * }
     */
    public function filterProviderChainWithDetails(array $providerNames, string $sourceType, array $context = []): array
    {
        $filteredProviderNames = [];
        $acceptedProviders = [];
        $skippedProviders = [];

        foreach ($providerNames as $providerName) {
            $providerName = trim((string) $providerName);

            if ($providerName === '') {
                continue;
            }

            if (in_array($providerName, $filteredProviderNames, true)) {
                continue;
            }

            if (! $this->isRegistered($providerName)) {
                $skippedProvider = $this->skippedProviderDetails($providerName, $sourceType, 'provider_not_registered');
                $skippedProviders[] = $skippedProvider;
                $this->logSkippedProvider($skippedProvider, $context);

                continue;
            }

            if (! $this->supportsSourceType($providerName, $sourceType)) {
                $skippedProvider = $this->skippedProviderDetails($providerName, $sourceType, 'source_type_not_supported');
                $skippedProviders[] = $skippedProvider;
                $this->logSkippedProvider($skippedProvider, $context);

                continue;
            }

            if (! $this->supportsAvailableInputMode($providerName, $sourceType)) {
                $skippedProvider = $this->skippedProviderDetails($providerName, $sourceType, 'input_mode_not_available');
                $skippedProviders[] = $skippedProvider;
                $this->logSkippedProvider($skippedProvider, $context);

                continue;
            }

            $filteredProviderNames[] = $providerName;
            $acceptedProviders[] = [
                'provider_name' => $providerName,
                'source_type' => $sourceType,
                'supported_source_types' => $this->supportedSourceTypes($providerName),
                'provider_input_modes' => $this->providerInputModes($providerName),
                'runtime_input_modes' => $this->runtimeInputModes($sourceType),
                'matched_input_modes' => $this->matchedInputModes($providerName, $sourceType),
            ];
        }

        return [
            'provider_chain' => $filteredProviderNames,
            'accepted_providers' => $acceptedProviders,
            'skipped_providers' => $skippedProviders,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function registeredProvidersSupporting(string $sourceType): array
    {
        return array_values(array_filter(
            array_keys($this->registeredProviders()),
            fn (string $providerName): bool => $this->supportsSourceType($providerName, $sourceType)
                && $this->supportsAvailableInputMode($providerName, $sourceType)
        ));
    }

    public function isRegistered(string $providerName): bool
    {
        return array_key_exists($providerName, $this->registeredProviders());
    }

    public function supportsSourceType(string $providerName, string $sourceType): bool
    {
        $sourceTypes = $this->supportedSourceTypes($providerName);

        if ($sourceTypes === []) {
            return false;
        }

        return in_array($sourceType, $sourceTypes, true);
    }

    public function supportsAvailableInputMode(string $providerName, string $sourceType): bool
    {
        return $this->matchedInputModes($providerName, $sourceType) !== [];
    }

    /**
     * @return array<int, string>
     */
    public function matchedInputModes(string $providerName, string $sourceType): array
    {
        return array_values(array_intersect(
            $this->providerInputModes($providerName),
            $this->runtimeInputModes($sourceType)
        ));
    }

    /**
     * @return array<int, string>
     */
    public function providerInputModes(string $providerName): array
    {
        $inputModes = config("ai_question_generation.provider_capabilities.{$providerName}.input_modes", []);

        return is_array($inputModes) ? array_values($inputModes) : [];
    }

    /**
     * @return array<int, string>
     */
    public function supportedSourceTypes(string $providerName): array
    {
        $sourceTypes = config("ai_question_generation.provider_capabilities.{$providerName}.source_types", []);

        return is_array($sourceTypes) ? array_values($sourceTypes) : [];
    }

    /**
     * @return array<int, string>
     */
    public function runtimeInputModes(string $sourceType): array
    {
        $runtimeInputModes = config("ai_question_generation.runtime_input_modes.{$sourceType}", []);

        return is_array($runtimeInputModes) ? array_values($runtimeInputModes) : [];
    }

    /**
     * @return array<string, class-string|string>
     */
    private function registeredProviders(): array
    {
        $providers = config('ai_question_generation.providers', []);

        return is_array($providers) ? $providers : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function skippedProviderDetails(string $providerName, string $sourceType, string $reason): array
    {
        return [
            'provider_name' => $providerName,
            'source_type' => $sourceType,
            'reason' => $reason,
            'supported_source_types' => $this->supportedSourceTypes($providerName),
            'provider_input_modes' => $this->providerInputModes($providerName),
            'runtime_input_modes' => $this->runtimeInputModes($sourceType),
            'matched_input_modes' => $this->matchedInputModes($providerName, $sourceType),
        ];
    }

    /**
     * @param array<string, mixed> $skippedProvider
     * @param array<string, mixed> $context
     */
    private function logSkippedProvider(array $skippedProvider, array $context): void
    {
        Log::info('AI question generation provider skipped by capability filter.', [
            ...$context,
            ...$skippedProvider,
        ]);
    }
}
