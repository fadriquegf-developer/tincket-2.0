<?php
    file_get_contents(sprintf(storage_path('app/')."mailing/%s/%s.%s", $mailing->brand->code_name, $mailing->content_file_name, \App\Models\Mailing::PLAIN_EXTENSION));
?>