<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Enums;

/**
 * License types used in dataset and model cards.
 *
 * Matches the License type from @huggingface/hub.
 */
enum License: string
{
    case Apache20 = 'apache-2.0';
    case Mit = 'mit';
    case Openrail = 'openrail';
    case BigscienceOpenrailM = 'bigscience-openrail-m';
    case CreativemlOpenrailM = 'creativeml-openrail-m';
    case BigscienceBloomRail10 = 'bigscience-bloom-rail-1.0';
    case BigcodeOpenrailM = 'bigcode-openrail-m';
    case Afl30 = 'afl-3.0';
    case Artistic20 = 'artistic-2.0';
    case Bsl10 = 'bsl-1.0';
    case Bsd = 'bsd';
    case Bsd2Clause = 'bsd-2-clause';
    case Bsd3Clause = 'bsd-3-clause';
    case Bsd3ClauseClear = 'bsd-3-clause-clear';
    case CUda = 'c-uda';
    case Cc = 'cc';
    case Cc010 = 'cc0-1.0';
    case CcBy20 = 'cc-by-2.0';
    case CcBy25 = 'cc-by-2.5';
    case CcBy30 = 'cc-by-3.0';
    case CcBy40 = 'cc-by-4.0';
    case CcBySa30 = 'cc-by-sa-3.0';
    case CcBySa40 = 'cc-by-sa-4.0';
    case CcByNc20 = 'cc-by-nc-2.0';
    case CcByNc30 = 'cc-by-nc-3.0';
    case CcByNc40 = 'cc-by-nc-4.0';
    case CcByNd40 = 'cc-by-nd-4.0';
    case CcByNcNd30 = 'cc-by-nc-nd-3.0';
    case CcByNcNd40 = 'cc-by-nc-nd-4.0';
    case CcByNcSa20 = 'cc-by-nc-sa-2.0';
    case CcByNcSa30 = 'cc-by-nc-sa-3.0';
    case CcByNcSa40 = 'cc-by-nc-sa-4.0';
    case CdlaSharing10 = 'cdla-sharing-1.0';
    case CdlaPermissive10 = 'cdla-permissive-1.0';
    case CdlaPermissive20 = 'cdla-permissive-2.0';
    case Wtfpl = 'wtfpl';
    case Ecl20 = 'ecl-2.0';
    case Epl10 = 'epl-1.0';
    case Epl20 = 'epl-2.0';
    case Etalab20 = 'etalab-2.0';
    case Eupl11 = 'eupl-1.1';
    case Agpl30 = 'agpl-3.0';
    case Gfdl = 'gfdl';
    case Gpl = 'gpl';
    case Gpl20 = 'gpl-2.0';
    case Gpl30 = 'gpl-3.0';
    case Lgpl = 'lgpl';
    case Lgpl21 = 'lgpl-2.1';
    case Lgpl30 = 'lgpl-3.0';
    case Isc = 'isc';
    case Lppl13c = 'lppl-1.3c';
    case MsPl = 'ms-pl';
    case Mpl20 = 'mpl-2.0';
    case OdcBy = 'odc-by';
    case Odbl = 'odbl';
    case OpenrailPlusPlus = 'openrail++';
    case Osl30 = 'osl-3.0';
    case Postgresql = 'postgresql';
    case Ofl11 = 'ofl-1.1';
    case Ncsa = 'ncsa';
    case Unlicense = 'unlicense';
    case Zlib = 'zlib';
    case Pddl = 'pddl';
    case LgplLr = 'lgpl-lr';
    case DeepfloydIfLicense = 'deepfloyd-if-license';
    case Llama2 = 'llama2';
    case Llama3 = 'llama3';
    case Llama31 = 'llama3.1';
    case Llama32 = 'llama3.2';
    case Llama33 = 'llama3.3';
    case Gemma = 'gemma';
    case AppleAscl = 'apple-ascl';
    case AppleAmlr = 'apple-amlr';
    case Unknown = 'unknown';
    case Other = 'other';
}
