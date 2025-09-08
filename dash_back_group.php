<div class="row text-center">
                            <?php if($user_type == "administrator" OR $user_type == "hr"){ ?>
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee_by_dept.php?page=1&active=active&dept=Administration'" style="cursor: pointer;">
                                <div class="card-box widget-flat border-custom bg-custom text-white">
                                    <i class="mdi mdi-account-convert"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_admin ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">Administration</p>
                                </div>
                            </div>
                            <?php } if($user_type == "administrator" OR $user_type == "hr" OR $user_dept == "Finance"){ ?>
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee_by_dept.php?page=1&active=active&dept=Finance'" style="cursor: pointer;">
                                <div class="card-box widget-flat border-purple bg-purple text-white">
                                    <i class="mdi mdi-account-convert"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_Finance ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">Finance</p>
                                </div>
                            </div>
                            <?php } if($user_type == "administrator" OR $user_type == "hr"){ ?>
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee_by_dept.php?page=1&active=active&dept=General'" style="cursor: pointer;">
                                <div class="card-box bg-primary widget-flat border-primary text-white">
                                    <i class="mdi mdi-account-convert"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_General ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">General</p>
                                </div>
                            </div>
                            <?php } if($user_type == "administrator" OR $user_type == "hr"){ ?>
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee_by_dept.php?page=1&active=active&dept=HRD and Housing'" style="cursor: pointer;">
                                <div class="card-box widget-flat border-success bg-success text-white">
                                    <i class="mdi mdi-account-convert"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_HRDandHousing ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">HRD and Housing</p>
                                </div>
                            </div>
                            <?php } if($user_type == "administrator" OR $user_type == "hr" OR $user_dept == "Inspection"){ ?>
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee_by_dept.php?page=1&active=active&dept=Inspection'" style="cursor: pointer;">
                                <div class="card-box widget-flat border-custom bg-custom text-white">
                                    <i class="mdi mdi-account-convert"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_Inspection ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">Inspection</p>
                                </div>
                            </div>
                            <?php } if($user_type == "administrator" OR $user_type == "hr"){ ?>
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee_by_dept.php?page=1&active=active&dept=IT'" style="cursor: pointer;">
                                <div class="card-box widget-flat border-purple bg-purple text-white">
                                    <i class="mdi mdi-account-convert"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_IT ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">IT</p>
                                </div>
                            </div>
                            <?php } if($user_type == "administrator" OR $user_type == "hr" OR $user_dept == "Maintenance"){ ?>
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee_by_dept.php?page=1&active=active&dept=Maintenance'" style="cursor: pointer;">
                                <div class="card-box bg-primary widget-flat border-primary text-white">
                                    <i class="mdi mdi-account-convert"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_Maintenance ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">Maintenance</p>
                                </div>
                            </div>
                            <?php } if($user_type == "administrator" OR $user_type == "hr"){ ?>
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee_by_dept.php?page=1&active=active&dept=Management'" style="cursor: pointer;">
                                <div class="card-box widget-flat border-success bg-success text-white">
                                    <i class="mdi mdi-account-convert"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_Management ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">Management</p>
                                </div>
                            </div>
                            <?php } if($user_type == "administrator" OR $user_type == "hr" OR $user_dept == "POS"){ ?>
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee_by_dept.php?page=1&active=active&dept=POS'" style="cursor: pointer;">
                                <div class="card-box widget-flat border-custom bg-custom text-white">
                                    <i class="mdi mdi-account-convert"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_POS ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">POS</p>
                                </div>
                            </div>
                            <?php } if($user_type == "administrator" OR $user_type == "hr" OR $user_dept == "Production"){ ?>
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee_by_dept.php?page=1&active=active&dept=Production'" style="cursor: pointer;">
                                <div class="card-box widget-flat border-purple bg-purple text-white">
                                    <i class="mdi mdi-account-convert"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_Production ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">Production</p>
                                </div>
                            </div>
                            <?php } if($user_type == "administrator" OR $user_type == "hr"){ ?>
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee_by_dept.php?page=1&active=active&dept=Public Relation'" style="cursor: pointer;">
                                <div class="card-box bg-primary widget-flat border-primary text-white">
                                    <i class="mdi mdi-account-convert"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_PublicRelation ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">Public Relation</p>
                                </div>
                            </div>
                            <?php } if($user_type == "administrator" OR $user_type == "hr" OR $user_dept == "Purchase"){ ?>
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee_by_dept.php?page=1&active=active&dept=Purchase'" style="cursor: pointer;">
                                <div class="card-box widget-flat border-success bg-success text-white">
                                    <i class="mdi mdi-account-convert"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_Purchase ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">Purchase</p>
                                </div>
                            </div>
                            <?php } if($user_type == "administrator" OR $user_type == "hr" OR $user_dept == "Sales Department"){ ?>
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee_by_dept.php?page=1&active=active&dept=Sales Department'" style="cursor: pointer;">
                                <div class="card-box widget-flat border-custom bg-custom text-white">
                                    <i class="mdi mdi-account-convert"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_SalesDepartment ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">Sales Department</p>
                                </div>
                            </div>
                            <?php } if($user_type == "administrator" OR $user_type == "hr" ){ ?>
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee_by_dept.php?page=1&active=active&dept=Transportation'" style="cursor: pointer;">
                                <div class="card-box widget-flat border-purple bg-purple text-white">
                                    <i class="mdi mdi-account-convert"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_Transportation ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">Transportation</p>
                                </div>
                            </div>
                            <?php } if($user_type == "administrator" OR $user_type == "hr" OR $user_dept == "Warehouse"){ ?>
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee_by_dept.php?page=1&active=active&dept=Warehouse'" style="cursor: pointer;">
                                <div class="card-box bg-primary widget-flat border-primary text-white">
                                    <i class="mdi mdi-account-convert"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_Warehouse ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">Warehouse</p>
                                </div>
                            </div>
                            <?php } ?>

                        </div>