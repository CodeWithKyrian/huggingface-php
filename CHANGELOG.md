# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-04

### Added
- **Global**: First stable release of the Hugging Face PHP client.
- **Hub**: Full repository management (create, delete, list models/datasets/spaces).
- **Inference**: Support for all major inference tasks (Text Generation, Summarization, Translation, Image Classification, Object Detection, etc.).
- **Inference**: Support for multiple providers (Hugging Face Inference API, Together AI, Replicate) via `InferenceProvider` enum.
- **Cache**: System-level cache management (`$hf->cache()`) to list, delete, and clear cached repositories.
- **Cache**: Smart blob deduplication system for storage efficiency.
- **Files**: Fluent builder for file operations (`upload`, `download`, `delete`) with commit support.
- **Search**: Powerful search API to filter models, datasets, and spaces.
- **Auth**: Token resolution from environment (`HF_TOKEN`) or local file system.

### Changed
- **Performance**: Optimized snapshot downloads with additive caching and local manifest checks (zero-latency file existence).
- **DX**: Streamlined factory usage with `HuggingFace::client($token)` for quick start.

### Fixed
- N/A (Initial Release)
