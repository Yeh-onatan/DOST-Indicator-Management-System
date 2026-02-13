<?php

namespace App\Constants;

/**
 * Agency-related constants
 *
 * Centralized constants for agency codes, clusters, and types
 * to avoid hardcoded magic strings throughout the codebase.
 */
class AgencyConstants
{
    // Agency Codes
    public const DOST_CO = 'DOST-CO';
    public const DOST_SECA = 'DOST-SECA';

    // Agency Clusters - OUSEC-STS handles these
    public const CLUSTER_SSI = 'ssi';
    public const CLUSTER_COLLEGIAL = 'collegial';

    // Agency Clusters - OUSEC-RD handles these
    public const CLUSTER_COUNCIL = 'council';
    public const CLUSTER_RDI = 'rdi';

    // Agency Clusters - OUSEC-RO handles regional offices
    public const CLUSTER_REGIONAL = 'regional';

    // Main cluster for central agencies
    public const CLUSTER_MAIN = 'main';

    // All OUSEC-STS clusters
    public const OUSEC_STS_CLUSTERS = [
        self::CLUSTER_SSI,
        self::CLUSTER_COLLEGIAL,
        self::CLUSTER_MAIN, // Main cluster goes to OUSEC-STS
    ];

    // All OUSEC-RD clusters
    public const OUSEC_RD_CLUSTERS = [
        self::CLUSTER_COUNCIL,
        self::CLUSTER_RDI,
    ];

    // All cluster values
    public const ALL_CLUSTERS = [
        self::CLUSTER_SSI,
        self::CLUSTER_COLLEGIAL,
        self::CLUSTER_COUNCIL,
        self::CLUSTER_RDI,
        self::CLUSTER_MAIN,
    ];

    /**
     * Check if a cluster is handled by OUSEC-STS
     */
    public static function isOUSECSTSCluster(string $cluster): bool
    {
        return in_array($cluster, self::OUSEC_STS_CLUSTERS);
    }

    /**
     * Check if a cluster is handled by OUSEC-RD
     */
    public static function isOUSECRDCluster(string $cluster): bool
    {
        return in_array($cluster, self::OUSEC_RD_CLUSTERS);
    }

    /**
     * Get the OUSEC type for a given cluster
     *
     * @param string $cluster The cluster to check
     * @return string|null 'ousec_sts', 'ousec_rd', 'ousec_ro', or null if unknown
     */
    public static function getOUSECTypeForCluster(string $cluster): ?string
    {
        if (self::isOUSECSTSCluster($cluster)) {
            return 'ousec_sts';
        }

        if (self::isOUSECRDCluster($cluster)) {
            return 'ousec_rd';
        }

        // Regional cluster or offices with region_id go to OUSEC-RO
        if ($cluster === self::CLUSTER_REGIONAL) {
            return 'ousec_ro';
        }

        return null;
    }
}
