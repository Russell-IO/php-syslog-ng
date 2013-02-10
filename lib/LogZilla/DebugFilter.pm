package LogZilla::DebugFilter;
use Filter::Simple;

FILTER_ONLY 
code => sub { s/\sDEBUG\(.*?\);//sg if $ENV{LZ_DISABLE_DEBUG}; }
;

1;
