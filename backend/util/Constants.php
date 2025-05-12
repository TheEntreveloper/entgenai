<?php
namespace ev\ai\util;

// --- Default AI API Providers
const entgenai_openai = 'OpenAI';
const entgenai_anthropic = 'Anthropic';
const entgenai_gemini = 'Gemini';
const entgenai_gemini_images = 'Gemini_image';
const entgenai_local_ollama = 'local_model';

// --- AI API Providers URLs
// --- OpenAI ---
const entgenai_config_openai_url = 'https://api.openai.com/v1/chat/completions';
// --- Anthropic ---
const entgenai_config_anthropicai_url = 'https://api.anthropic.com/v1/messages';
// --- Gemini ---
const entgenai_config_geminiai_url = 'https://generativelanguage.googleapis.com/v1beta/models/_LLMODEL:generateContent?key=_APIKEY';
// --- Local ---
const entgenai_config_localai_url = 'http://localhost:11434/api/chat'; // using ollama and model: llama3.2

const entgenai_config_options_label = 'entgenai_config_options'; // label for the plugins options

const entgenai_gentext_post_type = 'entgenai_gentxt';

const entgenai_config_ai_wlist_prefix = "This is the output from the AI. Please remove all text except the rows containing a word and its hint separated by ;;\nand remember that AIs can make mistakes, so review each line before saving the word list:\n";