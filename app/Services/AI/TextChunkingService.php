<?php

namespace App\Services\AI;

class TextChunkingService
{
    private int $chunkSize;
    private int $overlap;
    private string $separator;

    /**
     * @param int $chunkSize Maximum characters per chunk (default 1000)
     * @param int $overlap Characters to overlap between chunks (default 200)
     * @param string $separator Primary separator for chunking (default paragraph)
     */
    public function __construct(
        int $chunkSize = 1000,
        int $overlap = 200,
        string $separator = "\n\n"
    ) {
        $this->chunkSize = $chunkSize;
        $this->overlap = $overlap;
        $this->separator = $separator;
    }

    /**
     * Split text into chunks with overlap
     *
     * @param string $text
     * @return array Array of chunks with metadata
     */
    public function chunkText(string $text): array
    {
        $text = $this->normalizeText($text);

        if (mb_strlen($text) <= $this->chunkSize) {
            return [[
                'content' => $text,
                'start_position' => 0,
                'end_position' => mb_strlen($text),
                'chunk_index' => 0,
            ]];
        }

        $chunks = [];
        $paragraphs = $this->splitIntoParagraphs($text);

        $currentChunk = '';
        $currentStart = 0;
        $chunkIndex = 0;

        foreach ($paragraphs as $paragraph) {
            $paragraphLength = mb_strlen($paragraph);

            // If paragraph alone is too long, split it further
            if ($paragraphLength > $this->chunkSize) {
                // Save current chunk if not empty
                if (!empty($currentChunk)) {
                    $chunks[] = [
                        'content' => trim($currentChunk),
                        'start_position' => $currentStart,
                        'end_position' => $currentStart + mb_strlen($currentChunk),
                        'chunk_index' => $chunkIndex++,
                    ];
                    $currentChunk = '';
                }

                // Split long paragraph by sentences
                $subChunks = $this->splitLongParagraph($paragraph);
                foreach ($subChunks as $subChunk) {
                    $chunks[] = [
                        'content' => trim($subChunk),
                        'start_position' => $currentStart,
                        'end_position' => $currentStart + mb_strlen($subChunk),
                        'chunk_index' => $chunkIndex++,
                    ];
                    $currentStart += mb_strlen($subChunk);
                }
                continue;
            }

            // Check if adding paragraph would exceed chunk size
            $potentialLength = mb_strlen($currentChunk) + mb_strlen($paragraph);

            if ($potentialLength > $this->chunkSize && !empty($currentChunk)) {
                // Save current chunk
                $chunks[] = [
                    'content' => trim($currentChunk),
                    'start_position' => $currentStart,
                    'end_position' => $currentStart + mb_strlen($currentChunk),
                    'chunk_index' => $chunkIndex++,
                ];

                // Start new chunk with overlap
                $overlapText = $this->getOverlapText($currentChunk);
                $currentChunk = $overlapText . $paragraph;
                $currentStart += mb_strlen($currentChunk) - mb_strlen($overlapText);
            } else {
                // Add paragraph to current chunk
                $currentChunk .= ($currentChunk ? "\n\n" : '') . $paragraph;
            }
        }

        // Add final chunk if not empty
        if (!empty($currentChunk)) {
            $chunks[] = [
                'content' => trim($currentChunk),
                'start_position' => $currentStart,
                'end_position' => $currentStart + mb_strlen($currentChunk),
                'chunk_index' => $chunkIndex,
            ];
        }

        return $chunks;
    }

    /**
     * Normalize text (remove extra spaces, normalize line breaks)
     */
    private function normalizeText(string $text): string
    {
        // Normalize line breaks
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Remove excessive line breaks (more than 2)
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        // Remove excessive spaces
        $text = preg_replace("/[ \t]+/", " ", $text);

        return trim($text);
    }

    /**
     * Split text into paragraphs
     */
    private function splitIntoParagraphs(string $text): array
    {
        $paragraphs = explode($this->separator, $text);
        return array_filter($paragraphs, fn($p) => !empty(trim($p)));
    }

    /**
     * Split long paragraph by sentences
     */
    private function splitLongParagraph(string $paragraph): array
    {
        $chunks = [];
        $sentences = $this->splitIntoSentences($paragraph);

        $currentChunk = '';

        foreach ($sentences as $sentence) {
            $sentenceLength = mb_strlen($sentence);

            // If single sentence is too long, split by character count
            if ($sentenceLength > $this->chunkSize) {
                if (!empty($currentChunk)) {
                    $chunks[] = $currentChunk;
                    $currentChunk = '';
                }

                // Split by character count with word boundaries
                $charChunks = $this->splitByCharCount($sentence);
                $chunks = array_merge($chunks, $charChunks);
                continue;
            }

            $potentialLength = mb_strlen($currentChunk) + $sentenceLength;

            if ($potentialLength > $this->chunkSize && !empty($currentChunk)) {
                $chunks[] = $currentChunk;

                // Start new chunk with overlap
                $overlapText = $this->getOverlapText($currentChunk);
                $currentChunk = $overlapText . $sentence;
            } else {
                $currentChunk .= ($currentChunk ? ' ' : '') . $sentence;
            }
        }

        if (!empty($currentChunk)) {
            $chunks[] = $currentChunk;
        }

        return $chunks;
    }

    /**
     * Split text into sentences (Thai and English)
     */
    private function splitIntoSentences(string $text): array
    {
        // Split by common sentence endings
        $pattern = '/([.!?।]+[\s]*)/u';
        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        $sentences = [];
        for ($i = 0; $i < count($parts); $i += 2) {
            if (isset($parts[$i]) && !empty(trim($parts[$i]))) {
                $sentence = $parts[$i];
                if (isset($parts[$i + 1])) {
                    $sentence .= $parts[$i + 1];
                }
                $sentences[] = trim($sentence);
            }
        }

        return array_filter($sentences, fn($s) => !empty($s));
    }

    /**
     * Split text by character count while preserving word boundaries
     */
    private function splitByCharCount(string $text): array
    {
        $chunks = [];
        $words = preg_split('/\s+/u', $text);

        $currentChunk = '';

        foreach ($words as $word) {
            $wordLength = mb_strlen($word);
            $potentialLength = mb_strlen($currentChunk) + $wordLength + 1;

            if ($potentialLength > $this->chunkSize && !empty($currentChunk)) {
                $chunks[] = $currentChunk;
                $currentChunk = $word;
            } else {
                $currentChunk .= ($currentChunk ? ' ' : '') . $word;
            }
        }

        if (!empty($currentChunk)) {
            $chunks[] = $currentChunk;
        }

        return $chunks;
    }

    /**
     * Get overlap text from the end of current chunk
     */
    private function getOverlapText(string $text): string
    {
        if ($this->overlap <= 0 || mb_strlen($text) <= $this->overlap) {
            return '';
        }

        // Get last N characters
        $overlapText = mb_substr($text, -$this->overlap);

        // Find the start of the last complete sentence
        $lastSentenceEnd = max(
            mb_strrpos($overlapText, '.'),
            mb_strrpos($overlapText, '!'),
            mb_strrpos($overlapText, '?')
        );

        if ($lastSentenceEnd !== false && $lastSentenceEnd > 0) {
            $overlapText = mb_substr($overlapText, $lastSentenceEnd + 1);
        }

        return trim($overlapText) . ' ';
    }

    /**
     * Estimate token count for text
     */
    public function estimateTokens(string $text): int
    {
        // Rough estimate for mixed Thai/English:
        // English: ~4 chars per token
        // Thai: ~1.5 chars per token
        // We'll use average of 3 chars per token
        return (int) ceil(mb_strlen($text) / 3);
    }

    /**
     * Create chunks with token estimation
     */
    public function chunkTextWithTokens(string $text): array
    {
        $chunks = $this->chunkText($text);

        return array_map(function ($chunk) {
            $chunk['token_count'] = $this->estimateTokens($chunk['content']);
            return $chunk;
        }, $chunks);
    }
}
