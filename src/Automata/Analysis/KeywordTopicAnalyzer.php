<?php
// KeywordIntentAnalyzer.php
namespace BlueFission\Automata\Analysis;

use BlueFission\Str;
use BlueFission\Arr;
use BlueFission\Automata\Strategy\NaiveBayesTextClassification;
use BlueFission\Automata\Analysis\IAnalyzer;
use BlueFission\Automata\Context;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WhitespaceTokenizer;
use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\ModelManager;

class KeywordTopicAnalyzer implements IAnalyzer
{
    private $_topicClassifier;
    private $_modelDirPath;

    public function __construct(NaiveBayesTextClassification $topicClassifier, string $modelDirPath )
    {
        $this->_topicClassifier = $topicClassifier;
        $this->_modelDirPath = $modelDirPath;
    }

    public function analyze(string $input, Context $topic, array $dialogues): Arr
    {
        $scores = [];

        // Tokenize the input into words and convert to lowercase
        $inputWords = preg_split('/\s+/', strtolower($input));
        $inputWordCount = count($inputWords);

        $samples = [];
        $labels = [];

        foreach ($dialogues as $topic => $phrases) {
            // Calculate the score for this intent based on keywords, context, and other criteria.
            $score = 0;

            foreach ($phrases as $phrase) {
                $keyword = ['priority'=>$phrase['weight'], 'word'=>$phrase['text']];
                $keywordLower = strtolower($keyword['word']);
                $samples[] = $keyword['word'];
                $labels[] = $topic;
                
                foreach ($inputWords as $inputWord) {
                    $similarityPercent = 0;
                    similar_text($inputWord, $keywordLower, $similarityPercent);

                    // Convert the similarity percentage to a value between 0 and 1
                    $similarityScore = $similarityPercent / 100;

                    // Calculate the multiplier based on the input word count
                    $multiplier = 1 / $inputWordCount;

                    // Calculate the multiplier based on the input word length
                    // $multiplier = strlen($inputWord) / $multiplier;

                    // Increase the score based on the similarity and multiplier
                    $score += $keyword['priority'] * $similarityScore * $multiplier;
                }
            }

            $scores[$topic] = $score;
        }

        $class = (new \ReflectionClass($this))->getShortName();
        $modelName = Str::snake($class);

        $modelFilePath = $this->_modelDirPath.$modelName.'.phpml';
        $modelManager = new ModelManager();
        if ( file_exists($modelFilePath) ) {
            $loadedPipeline = $modelManager->restoreFromFile($modelFilePath);

            $this->_topicClassifier->setPipeline($loadedPipeline);
        } else {
            $this->_topicClassifier->train($samples, $labels);

            if ( $this->_modelDirPath ) {
                if ( !file_exists($this->_modelDirPath) ) {
                    mkdir($this->_modelDirPath);
                }

                $modelManager->saveToFile($this->_topicClassifier->getPipeline(), $modelFilePath);
            }
        }

        $classification = $this->_topicClassifier->predict($input);
        if ( !isset($scores[$classification]) ) {
            $scores[$classification] = 0;
        }

        $scores[$classification] += 5;

        if ( !empty($scores) ) {
            arsort($scores);
        }

        return Arr::make($scores);
    }

}
