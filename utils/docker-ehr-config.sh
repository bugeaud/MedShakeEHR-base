#!/bin/bash
#
# Perform the configuration duty, it consist of two steps :
#   1- Environment variable checking and initialization with default values
#   2- Creating default instances of config files injecting environement variables as required by legacy code
#
# Step 2 applies to all the project files ending with a +envsubst suffix. For each match, the envsubst command will generate a file with a name without the suffix with environement variable value injected.
# 
# Known files using this prefix :
#   - config/config.yml+envsubst : template file for a default configuration
#   - config/env.sh+envsubst : template file to store the EHR environment variables from the host to the EHR containers
#
# Current env used are :
#   - EHR_SERVER_NAME  : Name of the server, used in the web container Apache config and in the legacy config file config/config.yml (URL & cookie domain)
#   - EHR_SERVER_ALIAS : Alternative names of the server, used only in Apache application config
#   - EHR_DBMS_NAME : Alias used by MariaDB
#   - EHR_DBMS_ROOT_PASSWORD : The password for the root at MariaDB
#   - EHR_DBMS_USER_NAME :  The name of the database to use
#   - EHR_DBMS_USER_PASSWORD : The password of the database user's name 
#   - EHR_DBMS_VAR : Some random data used at the dbms level
#   - EHR_FINGERPRINT : Some random data used at the web level
#
# An additional procedure name can be passed as a the first parameter to specify the command to process :
#   - init : Initialize the configuration process. This is the default command if no parameter is given.
#   - clean : This clean the env and remove all the files generated (Warning : data might be lost as a direct consequence)
#

initEnvironment() {
 local targetEnv=$1
 local defaultValue=$2
 #echo "initEnvironment called targetEnv=>$targetEnv< defaultValue=>$defaultValue<"

 if [ -z "${!targetEnv}" ]; then
  echo "$(tput setaf 11)TODO$(tput sgr0) You can customize the env $targetEnv if required"
  if [ ! -z $defaultValue ]; then
   export "$targetEnv"="$defaultValue"
   echo "$(tput setaf 10)DONE$(tput sgr0) Defaulting $targetEnv to ${!targetEnv}"
  fi
 else
   echo "$(tput setaf 10)DONE$(tput sgr0) $targetEnv set to ${!targetEnv}"
 fi
}

cleanEnv() {
 export EHR_SERVER_NAME=
 export EHR_SERVER_ALIAS=
 export EHR_DBMS_NAME=
 export EHR_DBMS_ROOT_PASSWORD=
 export EHR_DBMS_USER_NAME=
 export EHR_DBMS_USER_PASSWORD=
 export EHR_DBMS_VAR=
 export EHR_FINGERPRINT=
}

generateRandom(){
 dd if=/dev/urandom bs=1 count=8 2>/dev/null | base64 -w 0
}

generatePassword(){
 head /dev/urandom | tr -dc A-Za-z0-9 | head -c 20 ; echo ''
}

overrideFunctionName() {
    local ORIG_FUNC=$(declare -f $1)
    local NEWNAME_FUNC="$2${ORIG_FUNC#$1}"
    eval "$NEWNAME_FUNC"
}

initEnv() {
 initEnvironment "EHR_SERVER_NAME" "www.example.net"
 initEnvironment "EHR_SERVER_ALIAS"
 initEnvironment "EHR_DBMS_NAME" "medshakeehr"
 initEnvironment "EHR_DBMS_ROOT_PASSWORD" $(generatePassword)
 initEnvironment "EHR_DBMS_USER_NAME" "mse-user"
 initEnvironment "EHR_DBMS_USER_PASSWORD" $(generatePassword)
 initEnvironment "EHR_DBMS_VAR" $(generateRandom)
 initEnvironment "EHR_FINGERPRINT" $(generateRandom)
}

replaceEnvSubst() {
 # Create instance of file with environement variable replaced
 for i in `find . -type f -name "*+envsubst"`; do
  echo "$(tput setaf 10)DONE$(tput sgr0) Generating ${i%+*} from $i"
  envsubst < "$i" > "${i%+*}"
  chown --reference="$i" "${i%+*}"
  chmod --reference="$i" "${i%+*}"
 done
}
removeEnvSubstInstance() {
 for i in `find . -type f -name "*+envsubst"`; do
  echo "$(tput setaf 10)DONE$(tput sgr0) Removing ${i%+*} generated from $i"
  rm "${i%+*}"
 done
}

copyGeneratedEnv(){
 if [ -e  ~/ehr/env.sh ]; then
  echo "$(tput setaf 11)WARN$(tput sgr0) ~/ehr/env.sh is already existing. We will not overwrite it to keep this existing configuration safe. To get a new configuration beeing generated, you will need to empty all ~/ehr first." 
 else
  cp config/env.sh ~/ehr/env.sh
  echo "$(tput setaf 10)DONE$(tput sgr0) env.sh containing the default env variable values is now saved as ~/ehr/env.sh"
 fi
}

clean(){
 cleanEnv
 removeEnvSubstInstance
}

cleanNoEnv(){
 removeEnvSubstInstance
}

init(){
 initEnv
 replaceEnvSubst
 copyGeneratedEnv
}

update(){
 replaceEnvSubst
 copyGeneratedEnv
}

calledProcedure=${1:-init}
#echo "Calling procedure >$calledProcedure< >>$1<< $@"

# Those two lines are required to be  written in any script using this library
#
#eval $calledProcedure
#echo "$(tput setaf 10)DONE$(tput sgr0) Called $calledProcedure"
