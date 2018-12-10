<?php

namespace Prometheus;

/**
 * Class RenderTextFormat
 * @package Prometheus
 */
class RenderTextFormat
{
    const MIME_TYPE = 'text/plain; version=0.0.4';

    /**
     * @param MetricFamilySamples[] $metrics
     * @return string
     */
    public function render(array $metrics)
    {
        usort($metrics, function (MetricFamilySamples $a, MetricFamilySamples $b) {
            return strcmp($a->getName(), $b->getName());
        });

        $lines = [];

        foreach ($metrics as $metric) {
            $lines[] = "# HELP " . $metric->getName() . " {$metric->getHelp()}";
            $lines[] = "# TYPE " . $metric->getName() . " {$metric->getType()}";
            foreach ($metric->getSamples() as $sample) {
                $lines[] = $this->renderSample($metric, $sample);
            }
        }
        return implode("\n", $lines) . "\n";
    }

    /**
     * @param MetricFamilySamples $metric
     * @param Sample $sample
     * @return string
     */
    private function renderSample(MetricFamilySamples $metric, Sample $sample)
    {
        $labelNames = $metric->getLabelNames();
        if ($metric->hasLabelNames() || $sample->hasLabelNames()) {
            $labelNames = array_merge($labelNames, $sample->getLabelNames());
            $escapedLabels = $this->getEscapedLabels($labelNames, $sample->getLabelValues());

            return $sample->getName() . '{' . implode(',', $escapedLabels) . '} ' . $sample->getValue();
        }
        return $sample->getName() . ' ' . $sample->getValue();
    }

    /**
     * @param array $labelNames
     * @param array $values
     * @return array
     */
    private function getEscapedLabels(array $labelNames, array $values) : array {
        $escapedLabels = [];
        if(count($labelNames) == count($values)) {
            $labels = array_combine($labelNames, $values);
            foreach ($labels as $labelName => $labelValue) {
                $escapedLabels[] = $labelName . '="' . $this->escapeLabelValue($labelValue) . '"';
            }
        }

        return $escapedLabels;
    }

    /**
     * @param $value
     * @return mixed
     */
    private function escapeLabelValue($value)
    {
        $value = str_replace("\\", "\\\\", $value);
        $value = str_replace("\n", "\\n", $value);
        $value = str_replace("\"", "\\\"", $value);
        return $value;
    }
}
