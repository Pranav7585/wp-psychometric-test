/* ==========================================================
   WP Psychometric Test Pro — app.js
   Version: 2.0.0
   ========================================================== */
(function ($) {
    'use strict';

    var App = {
        session_key:   '',
        participant:   null,
        current_test:  null,
        test_data:     null,
        timer_int:     null,
        time_elapsed:  0,
        chart_inst:    null,

        /* -------- Init -------- */
        init: function () {
            App.session_key = PsychoApp.session_key || '';
            App.participant  = PsychoApp.participant  || null;

            if ( App.session_key && App.participant ) {
                App.showStep( 'tests' );
                App.showWelcome( App.participant.name );
            } else {
                App.showStep( 'entry' );
            }

            App.bindEntry();
            App.bindTestList();
            App.bindExam();
            App.bindResults();
        },

        /* -------- Navigation -------- */
        showStep: function ( id ) {
            $( '.psycho-step' ).hide();
            $( '#psycho-step-' + id ).show();
            $( 'html, body' ).animate( { scrollTop: $( '#psycho-portal' ).offset().top - 20 }, 300 );
        },

        showWelcome: function ( name ) {
            $( '#psycho-welcome-name' ).text( name );
            $( '#psycho-welcome-bar' ).show();
        },

        /* -------- Messages -------- */
        showMsg: function ( selector, msg, type ) {
            type = type || 'error';
            var icon = type === 'success' ? '✅' : ( type === 'info' ? 'ℹ️' : '❌' );
            $( selector ).html(
                '<div class="psycho-msg psycho-msg-' + App.esc( type ) + '">' + App.esc( msg ) + '</div>'
            );
        },

        /* -------- Entry Form -------- */
        bindEntry: function () {
            $( '#psycho-entry-form' ).on( 'submit', function ( e ) {
                e.preventDefault();
                var name  = $.trim( $( '#psycho_name' ).val() );
                var email = $.trim( $( '#psycho_email' ).val() );
                var phone = $.trim( $( '#psycho_phone' ).val() );
                var consent = $( '#psycho_consent' ).is( ':checked' );

                $( '#psycho-entry-msg' ).html( '' );

                if ( ! name ) {
                    App.showMsg( '#psycho-entry-msg', 'Please enter your full name.' );
                    $( '#psycho_name' ).addClass( 'shake' ).on( 'animationend', function() { $( this ).removeClass( 'shake' ); } );
                    return;
                }
                if ( ! email || ! /\S+@\S+\.\S+/.test( email ) ) {
                    App.showMsg( '#psycho-entry-msg', 'Please enter a valid email address.' );
                    $( '#psycho_email' ).addClass( 'shake' ).on( 'animationend', function() { $( this ).removeClass( 'shake' ); } );
                    return;
                }
                if ( ! phone ) {
                    App.showMsg( '#psycho-entry-msg', 'Please enter your phone number.' );
                    $( '#psycho_phone' ).addClass( 'shake' ).on( 'animationend', function() { $( this ).removeClass( 'shake' ); } );
                    return;
                }
                if ( ! consent ) {
                    App.showMsg( '#psycho-entry-msg', 'Please agree to the Privacy Policy.' );
                    return;
                }

                var $btn = $( '#psycho-entry-btn' );
                $btn.find( '.btn-text' ).hide();
                $btn.find( '.btn-loader' ).show();
                $btn.prop( 'disabled', true );

                $.post( PsychoApp.ajax_url, {
                    action: 'psycho_register_participant',
                    nonce:  PsychoApp.nonce,
                    name:   name,
                    email:  email,
                    phone:  phone,
                }, function ( res ) {
                    $btn.find( '.btn-text' ).show();
                    $btn.find( '.btn-loader' ).hide();
                    $btn.prop( 'disabled', false );

                    if ( res.success ) {
                        App.session_key = res.data.session_key;
                        App.participant  = { name: res.data.name, email: email };
                        App.showWelcome( res.data.name );
                        App.showStep( 'tests' );
                    } else {
                        App.showMsg( '#psycho-entry-msg', res.data.message || PsychoApp.i18n.error_generic );
                    }
                } ).fail( function () {
                    $btn.find( '.btn-text' ).show();
                    $btn.find( '.btn-loader' ).hide();
                    $btn.prop( 'disabled', false );
                    App.showMsg( '#psycho-entry-msg', PsychoApp.i18n.error_generic );
                } );
            } );
        },

        /* -------- Test List -------- */
        bindTestList: function () {
            $( '#psycho-change-info' ).on( 'click', function () {
                App.session_key = '';
                App.participant  = null;
                document.cookie = 'psycho_session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                $( '#psycho-welcome-bar' ).hide();
                App.showStep( 'entry' );
            } );

            $( document ).on( 'click', '.psycho-start-test', function () {
                var $card      = $( this ).closest( '.psycho-test-card' );
                var test_id    = $card.data( 'test-id' );
                var test_name  = $card.data( 'test-name' );
                var has_passkey = $card.data( 'has-passkey' );
                var hint       = $card.data( 'passkey-hint' );

                App.current_test = { id: test_id, name: test_name };

                if ( has_passkey === 1 || has_passkey === '1' ) {
                    $( '#psycho-modal-test-name' ).text( test_name );
                    if ( hint ) {
                        $( '#psycho-modal-hint' ).text( 'Hint: ' + hint ).show();
                    } else {
                        $( '#psycho-modal-hint' ).hide();
                    }
                    $( '#psycho-passkey-input' ).val( '' );
                    $( '#psycho-passkey-msg' ).html( '' );
                    $( '#psycho-passkey-overlay' ).fadeIn( 200 );
                    setTimeout( function () { $( '#psycho-passkey-input' ).focus(); }, 250 );
                } else {
                    App.loadTest( test_id );
                }
            } );

            $( '#psycho-passkey-submit' ).on( 'click', function () {
                App.verifyPasskey();
            } );

            $( '#psycho-passkey-input' ).on( 'keydown', function ( e ) {
                if ( e.key === 'Enter' ) App.verifyPasskey();
            } );

            $( '#psycho-passkey-cancel, #psycho-passkey-overlay' ).on( 'click', function ( e ) {
                if ( e.target === this ) {
                    $( '#psycho-passkey-overlay' ).fadeOut( 150 );
                }
            } );

            $( '#psycho-passkey-modal' ).on( 'click', function ( e ) {
                e.stopPropagation();
            } );
        },

        /* -------- Verify Passkey -------- */
        verifyPasskey: function () {
            var passkey = $.trim( $( '#psycho-passkey-input' ).val() );
            if ( ! passkey ) {
                App.showMsg( '#psycho-passkey-msg', 'Please enter the passkey.', 'error' );
                $( '#psycho-passkey-input' ).addClass( 'shake' ).on( 'animationend', function () { $( this ).removeClass( 'shake' ); } );
                return;
            }

            var $btn = $( '#psycho-passkey-submit' );
            $btn.prop( 'disabled', true ).text( '…' );

            $.post( PsychoApp.ajax_url, {
                action:   'psycho_verify_passkey',
                nonce:    PsychoApp.nonce,
                test_id:  App.current_test.id,
                passkey:  passkey,
            }, function ( res ) {
                $btn.prop( 'disabled', false ).text( 'Unlock' );
                if ( res.success ) {
                    $( '#psycho-passkey-overlay' ).fadeOut( 150 );
                    App.loadTest( App.current_test.id );
                } else {
                    App.showMsg( '#psycho-passkey-msg', res.data.message || PsychoApp.i18n.error_generic );
                    $( '#psycho-passkey-input' ).addClass( 'shake' ).on( 'animationend', function () { $( this ).removeClass( 'shake' ); } );
                }
            } ).fail( function () {
                $btn.prop( 'disabled', false ).text( 'Unlock' );
                App.showMsg( '#psycho-passkey-msg', PsychoApp.i18n.error_generic );
            } );
        },

        /* -------- Load Test -------- */
        loadTest: function ( test_id ) {
            $.post( PsychoApp.ajax_url, {
                action:  'psycho_get_test_data',
                nonce:   PsychoApp.nonce,
                test_id: test_id,
            }, function ( res ) {
                if ( res.success ) {
                    App.test_data = res.data;
                    App.renderExam( res.data );
                    App.showStep( 'exam' );
                } else {
                    alert( res.data.message || PsychoApp.i18n.error_generic );
                }
            } ).fail( function () {
                alert( PsychoApp.i18n.error_generic );
            } );
        },

        /* -------- Render Exam -------- */
        renderExam: function ( data ) {
            var test      = data.test;
            var questions = data.questions;

            $( '#psycho-exam-title' ).text( test.title );
            if ( test.category ) {
                $( '#psycho-exam-category' ).text( test.category ).show();
            } else {
                $( '#psycho-exam-category' ).hide();
            }

            // Build questions
            var html = '';
            $.each( questions, function ( i, q ) {
                html += '<div class="psycho-q-block" data-qid="' + q.id + '">';
                html += '<div class="psycho-q-number">Question ' + ( i + 1 ) + ' of ' + questions.length + '</div>';
                html += '<div class="psycho-q-text">' + App.esc( q.question ) + '</div>';
                html += '<div class="psycho-options">';
                $.each( q.options, function ( j, opt ) {
                    var uid = 'psycho_q_' + q.id + '_opt_' + opt.id;
                    html += '<label class="psycho-option-label" for="' + uid + '">';
                    html += '<input type="radio" id="' + uid + '" name="psycho_q_' + q.id + '" value="' + opt.id + '">';
                    html += '<span class="psycho-radio-circle"></span>';
                    html += '<span>' + App.esc( opt.text ) + '</span>';
                    html += '</label>';
                } );
                html += '</div></div>';
            } );
            $( '#psycho-questions-container' ).html( html );

            // Timer
            clearInterval( App.timer_int );
            App.time_elapsed = 0;

            if ( test.time_limit > 0 ) {
                var limit_secs = test.time_limit * 60;
                var remaining  = limit_secs;

                $( '#psycho-timer-box' ).show();
                $( '#psycho-timer-display' ).text( App.fmtTime( remaining ) );

                App.timer_int = setInterval( function () {
                    App.time_elapsed++;
                    remaining = limit_secs - App.time_elapsed;

                    $( '#psycho-timer-display' ).text( App.fmtTime( remaining ) );

                    var $timerBox = $( '#psycho-timer-box' );
                    $timerBox.removeClass( 'warning danger' );
                    if ( remaining <= 60 ) {
                        $timerBox.addClass( 'danger' );
                    } else if ( remaining <= limit_secs * 0.25 ) {
                        $timerBox.addClass( 'warning' );
                    }

                    if ( remaining <= 0 ) {
                        clearInterval( App.timer_int );
                        alert( PsychoApp.i18n.time_up );
                        App.submitTest();
                    }
                }, 1000 );
            } else {
                $( '#psycho-timer-box' ).hide();
            }

            // Reset progress
            App.updateProgress( 0, questions.length );
            $( '#psycho-submit-test' ).prop( 'disabled', true );
        },

        /* -------- Exam Interactions -------- */
        bindExam: function () {
            $( document ).on( 'change', '.psycho-questions-container input[type="radio"]', function () {
                var $label = $( this ).closest( '.psycho-option-label' );
                var qid    = $( this ).closest( '.psycho-q-block' ).data( 'qid' );

                // Deselect siblings
                $( '.psycho-option-label[for^="psycho_q_' + qid + '"]' ).removeClass( 'selected' );
                $( '.psycho-q-block[data-qid="' + qid + '"] .psycho-option-label' ).removeClass( 'selected' );

                $label.addClass( 'selected' );
                $( this ).closest( '.psycho-q-block' ).addClass( 'answered' );

                var total    = App.test_data ? App.test_data.questions.length : 0;
                var answered = $( '.psycho-questions-container .psycho-q-block.answered' ).length;
                App.updateProgress( answered, total );

                if ( answered > 0 ) {
                    $( '#psycho-submit-test' ).prop( 'disabled', false );
                }
            } );

            $( '#psycho-submit-test' ).on( 'click', function () {
                App.submitTest();
            } );
        },

        /* -------- Submit Test -------- */
        submitTest: function () {
            if ( ! App.test_data ) return;

            var total    = App.test_data.questions.length;
            var answered = $( '.psycho-questions-container .psycho-q-block.answered' ).length;

            if ( answered < total ) {
                if ( ! confirm( PsychoApp.i18n.confirm_submit ) ) return;
            }

            var responses = {};
            $( '.psycho-questions-container input[type="radio"]:checked' ).each( function () {
                var qid  = $( this ).closest( '.psycho-q-block' ).data( 'qid' );
                var oid  = $( this ).val();
                responses[ qid ] = oid;
            } );

            var $btn = $( '#psycho-submit-test' );
            $btn.prop( 'disabled', true );
            $btn.find( '.btn-text' ).hide();
            $btn.find( '.btn-loader' ).show();

            clearInterval( App.timer_int );

            $.post( PsychoApp.ajax_url, {
                action:     'psycho_submit_test',
                nonce:      PsychoApp.nonce,
                test_id:    App.current_test.id,
                responses:  responses,
                time_taken: App.time_elapsed,
            }, function ( res ) {
                $btn.prop( 'disabled', false );
                $btn.find( '.btn-text' ).show();
                $btn.find( '.btn-loader' ).hide();

                if ( res.success ) {
                    App.showStep( 'results' );
                    App.fetchAndRenderResult( res.data.result_id );
                } else {
                    alert( res.data.message || PsychoApp.i18n.error_generic );
                }
            } ).fail( function () {
                $btn.prop( 'disabled', false );
                $btn.find( '.btn-text' ).show();
                $btn.find( '.btn-loader' ).hide();
                alert( PsychoApp.i18n.error_generic );
            } );
        },

        /* -------- Progress -------- */
        updateProgress: function ( answered, total ) {
            var pct = total > 0 ? Math.round( answered / total * 100 ) : 0;
            $( '#psycho-progress-fill' ).css( 'width', pct + '%' );
            $( '#psycho-progress-text' ).text( answered + ' of ' + total + ' answered' );
            $( '#psycho-progress-pct' ).text( pct + '%' );

            var unanswered = total - answered;
            if ( unanswered > 0 ) {
                $( '#psycho-unanswered-note' ).text( unanswered + ' question(s) unanswered' ).show();
            } else {
                $( '#psycho-unanswered-note' ).hide();
            }
        },

        /* -------- Fetch Result -------- */
        fetchAndRenderResult: function ( result_id ) {
            $.post( PsychoApp.ajax_url, {
                action:    'psycho_get_result',
                nonce:     PsychoApp.nonce,
                result_id: result_id,
            }, function ( res ) {
                if ( res.success ) {
                    App.renderResult( res.data );
                }
            } );
        },

        /* -------- Render Result -------- */
        renderResult: function ( d ) {
            $( '#psycho-result-emoji' ).text( d.result_icon || '🎯' );
            $( '#psycho-result-participant-name' ).text( d.participant_name );
            $( '#psycho-result-test-name' ).text( d.test_title );
            $( '#psycho-result-label' ).text( d.result_label ).css( 'color', d.result_color );
            $( '#psycho-result-desc' ).text( d.result_desc );

            // Recommendation
            if ( d.recommendation ) {
                $( '#psycho-recommendation-text' ).text( d.recommendation );
                $( '#psycho-recommendation-box' ).show();
            } else {
                $( '#psycho-recommendation-box' ).hide();
            }

            // Score ring animation
            var circumference = 326.73;
            var pct           = Math.min( 1, d.total_score / 100 );
            var dashoffset    = circumference * ( 1 - pct );

            $( '#psycho-ring-fill' )
                .attr( 'stroke', d.result_color )
                .css( 'transition', 'stroke-dashoffset 1.2s ease' );

            setTimeout( function () {
                $( '#psycho-ring-fill' ).attr( 'stroke-dashoffset', dashoffset );
            }, 200 );

            // Count-up for score
            $( { n: 0 } ).animate( { n: d.total_score }, {
                duration: 1200,
                step: function () {
                    $( '#psycho-score-number' ).text( Math.round( this.n ) );
                },
                complete: function () {
                    $( '#psycho-score-number' ).text( d.total_score );
                }
            } );

            // Traits
            if ( d.trait_scores && Object.keys( d.trait_scores ).length ) {
                $( '#psycho-traits-card' ).show();
                var traits     = d.trait_scores;
                var traitKeys  = Object.keys( traits );
                var values     = traitKeys.map( function ( k ) { return traits[ k ]; } );
                var maxVal     = Math.max.apply( null, values ) || 1;

                var barsHtml = '';
                $.each( traitKeys, function ( i, key ) {
                    var score = traits[ key ];
                    var pctW  = Math.min( 100, Math.round( score / maxVal * 100 ) );
                    barsHtml += '<div class="psycho-trait-row">';
                    barsHtml += '<div class="psycho-trait-name"><span>' + App.esc( key ) + '</span><span>' + score + '</span></div>';
                    barsHtml += '<div class="psycho-trait-bar-bg"><div class="psycho-trait-bar-fill" style="width:0" data-w="' + pctW + '"></div></div>';
                    barsHtml += '</div>';
                } );
                $( '#psycho-traits-list' ).html( barsHtml );

                // Animate bars
                setTimeout( function () {
                    $( '.psycho-trait-bar-fill' ).each( function () {
                        $( this ).css( 'width', $( this ).data( 'w' ) + '%' );
                    } );
                }, 300 );

                // Radar chart
                if ( App.chart_inst ) {
                    App.chart_inst.destroy();
                    App.chart_inst = null;
                }
                var ctx = document.getElementById( 'psycho-radar-chart' );
                if ( ctx && typeof Chart !== 'undefined' ) {
                    App.chart_inst = new Chart( ctx, {
                        type: 'radar',
                        data: {
                            labels: traitKeys,
                            datasets: [ {
                                label: d.test_title,
                                data:  values,
                                backgroundColor: 'rgba(108,99,255,0.18)',
                                borderColor: d.result_color || '#6c63ff',
                                borderWidth: 2,
                                pointBackgroundColor: d.result_color || '#6c63ff',
                                pointRadius: 5,
                            } ]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                r: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(0,0,0,0.07)' },
                                    pointLabels: { font: { size: 13, weight: '600' } },
                                }
                            },
                            plugins: { legend: { display: false } }
                        }
                    } );
                }
            } else {
                $( '#psycho-traits-card' ).hide();
            }

            // PDF button
            if ( d.pdf_url ) {
                $( '#psycho-pdf-btn' ).attr( 'href', d.pdf_url ).show();
            } else {
                $( '#psycho-pdf-btn' ).hide();
            }

            // Confetti
            App.launchConfetti();
        },

        /* -------- Results actions -------- */
        bindResults: function () {
            $( '#psycho-retake-btn' ).on( 'click', function () {
                App.showStep( 'tests' );
            } );
        },

        /* -------- Confetti -------- */
        launchConfetti: function () {
            var colors = [ '#6c63ff', '#f50057', '#00c853', '#ff9800', '#2196f3', '#e91e63', '#ffc107' ];
            var $wrap  = $( '#psycho-confetti-wrap' ).empty();
            for ( var i = 0; i < 80; i++ ) {
                var $p = $( '<div class="psycho-confetti-piece"></div>' );
                $p.css( {
                    left:            Math.random() * 100 + 'vw',
                    top:             '-20px',
                    background:      colors[ Math.floor( Math.random() * colors.length ) ],
                    width:           ( 6 + Math.random() * 8 ) + 'px',
                    height:          ( 6 + Math.random() * 8 ) + 'px',
                    borderRadius:    Math.random() > 0.5 ? '50%' : '2px',
                    animationDuration: ( 1.5 + Math.random() * 2 ) + 's',
                    animationDelay:  ( Math.random() * 1.5 ) + 's',
                } );
                $wrap.append( $p );
            }
            setTimeout( function () { $wrap.empty(); }, 5000 );
        },

        /* -------- Helpers -------- */
        fmtTime: function ( secs ) {
            if ( secs < 0 ) secs = 0;
            var m = Math.floor( secs / 60 );
            var s = secs % 60;
            return ( m < 10 ? '0' : '' ) + m + ':' + ( s < 10 ? '0' : '' ) + s;
        },

        esc: function ( str ) {
            return $( '<div>' ).text( str || '' ).html();
        }
    };

    $( document ).ready( function () {
        if ( $( '#psycho-portal' ).length ) {
            App.init();
        }
    } );

}( jQuery ) );
