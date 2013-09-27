#!/usr/bin/env bash
ROOT_PATH=`pwd`
VENDOR_PATH="${ROOT_PATH}/framework/vendor/"
PHING_COMMAND="${VENDOR_PATH}Phing/bin/phing"

chmod +x "${PHING_COMMAND}"
chmod +x "${VENDOR_PATH}Propel/generator"
chmod +x "${ROOT_PATH}/regenix"

export PATH=$PATH:$ROOT_PATH/regenix

echo "Install success.";