<?php
declare(strict_types=1);

/** Friendly display only; the original address remains unchanged in security records. */
function pureViaIpLabel(?string $address): string
{
    $address=trim($address??'');
    if(!filter_var($address,FILTER_VALIDATE_IP))return 'Unknown';
    // Display the IPv4 loopback equivalent while preserving the stored IPv6 value.
    if(inet_pton($address)===inet_pton('::1'))return '127.0.0.1';
    return $address;
}
