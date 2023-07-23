##
# @package     Triangle Console Plugin
# @link        https://github.com/Triangle-org/Console
#
# @author      Ivan Zorin <creator@localzet.com>
# @copyright   Copyright (c) 2018-2023 Localzet Group
# @license     GNU Affero General Public License, version 3
#
#              This program is free software: you can redistribute it and/or modify
#              it under the terms of the GNU Affero General Public License as
#              published by the Free Software Foundation, either version 3 of the
#              License, or (at your option) any later version.
#
#              This program is distributed in the hope that it will be useful,
#              but WITHOUT ANY WARRANTY; without even the implied warranty of
#              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#              GNU Affero General Public License for more details.
#
#              You should have received a copy of the GNU Affero General Public License
#              along with this program. If not, see <https://www.gnu.org/licenses/>.
#

_sf_{{ COMMAND_NAME }}() {
    # Use newline as only separator to allow space in completion values
    IFS=$'\n'
    local sf_cmd="${COMP_WORDS[0]}"

    # for an alias, get the real script behind it
    sf_cmd_type=$(type -t $sf_cmd)
    if [[ $sf_cmd_type == "alias" ]]; then
        sf_cmd=$(alias $sf_cmd | sed -E "s/alias $sf_cmd='(.*)'/\1/")
    elif [[ $sf_cmd_type == "file" ]]; then
        sf_cmd=$(type -p $sf_cmd)
    fi

    if [[ $sf_cmd_type != "function" && ! -x $sf_cmd ]]; then
        return 1
    fi

    local cur prev words cword
    _get_comp_words_by_ref -n := cur prev words cword

    local completecmd=("$sf_cmd" "_complete" "--no-interaction" "-sbash" "-c$cword" "-S{{ VERSION }}")
    for w in ${words[@]}; do
        w=$(printf -- '%b' "$w")
        # remove quotes from typed values
        quote="${w:0:1}"
        if [ "$quote" == \' ]; then
            w="${w%\'}"
            w="${w#\'}"
        elif [ "$quote" == \" ]; then
            w="${w%\"}"
            w="${w#\"}"
        fi
        # empty values are ignored
        if [ ! -z "$w" ]; then
            completecmd+=("-i$w")
        fi
    done

    local sfcomplete
    if sfcomplete=$(${completecmd[@]} 2>&1); then
        local quote suggestions
        quote=${cur:0:1}

        # Use single quotes by default if suggestions contains backslash (FQCN)
        if [ "$quote" == '' ] && [[ "$sfcomplete" =~ \\ ]]; then
            quote=\'
        fi

        if [ "$quote" == \' ]; then
            # single quotes: no additional escaping (does not accept ' in values)
            suggestions=$(for s in $sfcomplete; do printf $'%q%q%q\n' "$quote" "$s" "$quote"; done)
        elif [ "$quote" == \" ]; then
            # double quotes: double escaping for \ $ ` "
            suggestions=$(for s in $sfcomplete; do
                s=${s//\\/\\\\}
                s=${s//\$/\\\$}
                s=${s//\`/\\\`}
                s=${s//\"/\\\"}
                printf $'%q%q%q\n' "$quote" "$s" "$quote";
            done)
        else
            # no quotes: double escaping
            suggestions=$(for s in $sfcomplete; do printf $'%q\n' $(printf '%q' "$s"); done)
        fi
        COMPREPLY=($(IFS=$'\n' compgen -W "$suggestions" -- $(printf -- "%q" "$cur")))
        __ltrim_colon_completions "$cur"
    else
        if [[ "$sfcomplete" != *"Command \"_complete\" is not defined."* ]]; then
            >&2 echo
            >&2 echo $sfcomplete
        fi

        return 1
    fi
}

complete -F _sf_{{ COMMAND_NAME }} {{ COMMAND_NAME }}