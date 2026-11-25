<?php

// Payment details shown to members.
// QR: save your real payment QR image as  assets/images/payment-qr.png  (jpg/jpeg/webp also work).
//     It replaces the bundled SAMPLE placeholder (assets/images/payment-qr.svg), which is not a real QR.
return [
    // Premium subscription page (unchanged)
    'method_name' => 'Manual bank / wallet transfer',
    'instructions' => 'Contact FitTrack administration for the official payment details before making a transfer.',
    'currency' => 'NPR',

    // Gym membership + personal coach payment portal
    'cash_instructions' => 'Pay the amount in cash at the gym reception. Your request stays pending until the admin confirms the cash was received.',
    'qr_account_name' => 'FitTrack Gym',                       // shown next to the QR code ('' to hide)
    'qr_note' => 'Use any bank or wallet app that can scan this QR code.', // ('' to hide)
    // 'qr_image' => 'assets/images/my-custom-qr.png',         // optional: use a different file name
];
