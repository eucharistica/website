<?php
// Buat OAuth Client di Google Cloud Console
// Authorized redirect URI: https://<domainKamu>/websitev2/oauth_google_callback.php

const GOOGLE_CLIENT_ID     = '';
const GOOGLE_CLIENT_SECRET = '';
const GOOGLE_REDIRECT_URI  = 'https://website.rsudmatraman.my.id/oauth_google_callback.php';

// Opsional: batasi domain email yang boleh login (kosongkan untuk bebas)
const GOOGLE_ALLOWED_DOMAINS = 'jakarta.go.id,rsudmatraman.my.id';
// Opsional whitelist per-email (menang atas domain)
const GOOGLE_ADMIN_EMAILS  = 'simrs.rsu.matraman@gmail.com'; 
const GOOGLE_EDITOR_EMAILS = 'humas.rsudmatraman@gmail.com'; 
