package LogZilla::Util::Snare;
use base 'Exporter';

# Tools for parsing/deparsing Windows Snare log format

our @EXPORT_OK = qw(
    build_snare_msg
);
our %EXPORT_TAGS = ( all => \@EXPORT_OK );


my @FIELDS = qw(signature criticality_num criticality event_log_source snare_counter submit_time
    event_id source_name user_name sid_type event_log_type computer_name category_string
    data_string expanded_string);

sub default_data {
    return {
        signature => 'MSWinEventLog',
        criticality => 1,
    };
}

# This is for tests to generate log entry as it is usually written to syslog
sub build_snare_msg {
    my( $data ) = @_;

    return join( "\\011",
        "MSWinEventLog",
        1,
        $data->{source} || "Security",
        12345,
        scalar localtime(),
        $data->{eid} || 444,
        $data->{source} || "Security",
        "s-WMI",
        "User",
        "Success Audit",
        "SOME-HOST",
        "Login/Logoff",
        "",
        "Successful Network Logon: ...",
        98765,
    );
}

1;
